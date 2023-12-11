<?php

namespace App\Http\Controllers;

use App\DataTables\HolidayDataTable;
use App\Helper\Reply;
use App\Http\Requests\CommonRequest;
use App\Http\Requests\Holiday\CreateRequest;
use App\Http\Requests\Holiday\UpdateRequest;
use App\Models\AttendanceSetting;
use App\Models\GoogleCalendarModule;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Services\Google;
use Illuminate\Support\Facades\DB;

class HolidayController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.holiday';
    }

    public function index(HolidayDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_holiday');
        abort_403(!in_array($viewPermission, ['all', 'added']));

        $this->currentYear = now()->format('Y');
        $this->currentMonth = now()->month;

        /* year range from last 5 year to next year */
        $years = [];
        $latestFifthYear = (int)Carbon::now()->subYears(5)->format('Y');
        $nextYear = (int)Carbon::now()->addYear()->format('Y');

        for ($i = $latestFifthYear; $i <= $nextYear; $i++) {
            $years[] = $i;
        }

        $this->years = $years;

        /* months */
        $this->months = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];

        return $dataTable->render('holiday.index', $this->data);

    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|mixed|void
     */
    public function create()
    {
        $this->addPermission = user()->permission('add_holiday');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        if (request()->ajax()) {
            $this->pageTitle = __('app.menu.holiday');
            $html = view('holiday.ajax.create', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'holiday.ajax.create';

        return view('holiday.create', $this->data);

    }

    /**
     *
     * @param CreateRequest $request
     * @return void
     */
    public function store(CreateRequest $request)
    {
        $this->addPermission = user()->permission('add_holiday');

        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $occassions = $request->occassion;
        $dates = $request->date;

        foreach ($dates as $index => $value) {
            if ($value != '') {

                $holiday = new Holiday();
                $holiday->date = Carbon::createFromFormat($this->global->date_format, $value)->format('Y-m-d');
                $holiday->occassion = $occassions[$index];
                $holiday->save();

                if($holiday) {
                    $holiday->event_id = $this->googleCalendarEvent($holiday);
                    $holiday->save();
                }
            }
        }

        if (request()->has('type')) {
            return redirect(route('holidays.index'));
        }

        $redirectUrl = urldecode($request->redirect_url);

        if ($redirectUrl == '') {
            $redirectUrl = route('holidays.index');
        }

        return Reply::successWithData(__('messages.holidayAddedSuccess'), ['redirectUrl' => $redirectUrl]);

    }

    /**
     * Display the specified holiday.
     */
    public function show(Holiday $holiday)
    {
        $this->holiday = $holiday;
        $this->viewPermission = user()->permission('view_holiday');
        abort_403(!($this->viewPermission == 'all' || ($this->viewPermission == 'added' && $this->holiday->added_by == user()->id)));

        $this->pageTitle = __('app.menu.holiday');

        if (request()->ajax()) {
            $html = view('holiday.ajax.show', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'holiday.ajax.show';

        return view('holiday.create', $this->data);

    }

    /**
     * @param Holiday $holiday
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|mixed|void
     */
    public function edit(Holiday $holiday)
    {
        $this->holiday = $holiday;
        $this->editPermission = user()->permission('edit_holiday');

        abort_403(!($this->editPermission == 'all' || ($this->editPermission == 'added' && $this->holiday->added_by == user()->id)));

        $this->pageTitle = __('app.menu.holiday');

        if (request()->ajax()) {
            $html = view('holiday.ajax.edit', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'holiday.ajax.edit';

        return view('holiday.create', $this->data);

    }

    /**
     * @param UpdateRequest $request
     * @param Holiday $holiday
     * @return array|void
     */
    public function update(UpdateRequest $request, Holiday $holiday)
    {
        $this->editPermission = user()->permission('edit_holiday');
        abort_403(!($this->editPermission == 'all' || ($this->editPermission == 'added' && $this->holiday->added_by == user()->id)));

        $data = $request->all();
        $data['date'] = Carbon::createFromFormat($this->global->date_format, $request->date)->format('Y-m-d');

        $holiday->update($data);

        if($holiday){
            $holiday->event_id = $this->googleCalendarEvent($holiday);
            $holiday->save();
        }

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('holidays.index')]);

    }

    /**
     * @param Holiday $holiday
     * @return array|void
     */
    public function destroy(Holiday $holiday)
    {
        $deletePermission = user()->permission('delete_holiday');
        abort_403(!($deletePermission == 'all' || ($deletePermission == 'added' && $holiday->added_by == user()->id)));

        $holiday->delete();
        return Reply::successWithData(__('messages.holidayDeletedSuccess'), ['redirectUrl' => route('holidays.index')]);

    }

    public function calendar(Request $request)
    {
        $this->viewPermission = user()->permission('view_holiday');

        abort_403(!($this->viewPermission == 'all' || $this->viewPermission == 'added'));

        $this->pageTitle = 'app.menu.calendar';

        if (request('start') && request('end')) {
            $holidayArray = array();

            $holidays = Holiday::orderBy('date', 'ASC');

            if (request()->searchText != '') {
                $holidays->where('holidays.occassion', 'like', '%' . request()->searchText . '%');
            }

            $holidays = $holidays->get();

            foreach ($holidays as $key => $holiday) {

                $holidayArray[] = [
                    'id' => $holiday->id,
                    'title' => $holiday->occassion,
                    'start' => $holiday->date->format('Y-m-d'),
                    'end' => $holiday->date->format('Y-m-d'),
                ];
            }

            return $holidayArray;
        }

        return view('holiday.calendar.index', $this->data);

    }

    public function applyQuickAction(Request $request)
    {
        abort_403(!in_array(user()->permission('edit_leave'), ['all', 'added']));

        if ($request->action_type === 'delete') {
            $this->deleteRecords($request);
            return Reply::success(__('messages.deleteSuccess'));
        }

        return Reply::error(__('messages.selectAction'));


    }

    protected function deleteRecords($request)
    {
        abort_403(user()->permission('delete_holiday') != 'all');

        Holiday::whereIn('id', explode(',', $request->row_ids))->delete();
    }

    public function markHoliday()
    {
        $this->addPermission = user()->permission('add_holiday');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->weekMap = [
            0 => __('app.sunday'),
            1 => __('app.monday'),
            2 => __('app.tuesday'),
            3 => __('app.wednesday'),
            4 => __('app.thursday'),
            5 => __('app.friday'),
            6 => __('app.saturday'),
        ];

        $this->holidaysArray = $this->weekMap;

        return view('holiday.mark-holiday.index', $this->data);
    }

    public function missingNumber($num_list)
    {
        // construct a new array
        $new_arr = range(1, 7);

        if(is_null($num_list))
        {
            return $new_arr;
        }

        return array_diff($new_arr, $num_list);
    }

    public function markDayHoliday(CommonRequest $request)
    {
        $this->addPermission = user()->permission('add_holiday');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        if (!$request->has('office_holiday_days')) {
            return Reply::error(__('messages.checkDayHoliday'));
        }

        $year = now()->format('Y');

        if ($request->has('year')) {
            $year = $request->has('year');
        }

        $dayss = [];
        $this->days = AttendanceSetting::WEEKDAYS;;

        if ($request->office_holiday_days != null && count($request->office_holiday_days) > 0) {
            foreach ($request->office_holiday_days as $holiday) {
                $dayss[] = $this->days[($holiday)];
                $day = $holiday;

                $dateArray = $this->getDateForSpecificDayBetweenDates($year . '-01-01', $year . '-12-31', ($day));

                foreach ($dateArray as $date) {
                    Holiday::firstOrCreate([
                        'date' => $date,
                        'occassion' => $this->days[$day]
                    ]);
                }

                $this->googleCalendarEventMulti($day, $year, $this->days);

            }
        }

        return Reply::successWithData(__('messages.holidayAddedSuccess'), ['redirectUrl' => route('holidays.index')]);
    }

    public function getDateForSpecificDayBetweenDates($startDate, $endDate, $weekdayNumber)
    {
        $startDate = strtotime($startDate);
        $endDate = strtotime($endDate);

        $dateArr = [];

        do {
            if (date('w', $startDate) != $weekdayNumber) {
                $startDate += (24 * 3600); // add 1 day
            }
        } while (date('w', $startDate) != $weekdayNumber);


        while ($startDate <= $endDate) {
            $dateArr[] = date('Y-m-d', $startDate);
            $startDate += (7 * 24 * 3600); // add 7 days
        }

        return ($dateArr);
    }

    public function salesPerformence(Request $request)
    {
        $salesId = $request->input('salesID');
        $this->username = DB::table('users')->where('id',$salesId)->value('name');
        $startDate = $request->input('start_date');
        $endDate1 = $request->input('end_date');
        $endDate = Carbon::parse($endDate1)->addDays(1)->format('Y-m-d');
        $exclude_end_date = Carbon::parse($endDate)->subDays(45)->format('Y-m-d');

        $this->startDate1 = Carbon::parse($startDate);
        $this->endDate1 = Carbon::parse($endDate1);

        $this->number_of_leads_received_fixed = DB::table('leads')
            ->where('added_by', $salesId)
            ->where('project_type','fixed')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate)
            ->count();

        $this->number_of_leads_received_hourly = DB::table('leads')
           ->where('added_by', $salesId)
           ->where('project_type', 'hourly')
           ->where('created_at', '>=', $startDate)
           ->where('created_at', '<', $endDate)
           ->count();

        $this->number_of_leads_received = DB::table('leads')
            ->where('added_by', $salesId)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate)
            ->count();

        $this->number_of_leads_convert_deals_fixed = DB::table('deal_stages')
            ->join('leads', 'deal_stages.lead_id', '=', 'leads.id')
            ->where('leads.added_by', $salesId)
            ->where('leads.project_type', 'fixed')
            ->where('leads.created_at', '>=', $startDate)
            ->where('leads.created_at', '<', $endDate)
            ->where('deal_stages.created_at', '>=', $startDate)
            ->where('deal_stages.created_at', '<', $endDate)
            ->count();

        $this->number_of_leads_convert_deals_hourly = DB::table('deal_stages')
            ->join('leads', 'deal_stages.lead_id', '=', 'leads.id')
            ->where('leads.added_by', $salesId)
            ->where('leads.project_type', 'hourly')
            ->where('leads.created_at', '>=', $startDate)
            ->where('leads.created_at', '<', $endDate)
            ->where('deal_stages.created_at', '>=', $startDate)
            ->where('deal_stages.created_at', '<', $endDate)
            ->count();

        $this->number_of_leads_convert_deals = DB::table('deal_stages')
            ->join('leads', 'deal_stages.lead_id', '=', 'leads.id')
            ->where('leads.added_by', $salesId)
            ->where('leads.created_at', '>=', $startDate)
            ->where('leads.created_at', '<', $endDate)
            ->where('deal_stages.created_at', '>=', $startDate)
            ->where('deal_stages.created_at', '<', $endDate)
            ->count();

        $this->number_of_leads_convert_won_deals_fixed = DB::table('deal_stages')
            ->join('leads', 'deal_stages.lead_id', '=', 'leads.id')
            ->join('deals', 'deal_stages.lead_id', '=', 'deals.lead_id')
            ->join('p_m_projects', 'deals.id', '=', 'p_m_projects.deal_id')
            ->where('leads.added_by', $salesId)
            ->where('leads.project_type', 'fixed')
            ->where('leads.created_at', '>=', $startDate)
            ->where('leads.created_at', '<', $endDate)
            ->where('deal_stages.created_at', '>=', $startDate)
            ->where('deal_stages.created_at', '<', $endDate)
            ->where('deals.created_at', '>=', $startDate)
            ->where('deals.created_at', '<', $endDate)
            ->count();

        $this->number_of_leads_convert_won_deals_hourly = DB::table('deal_stages')
            ->join('leads', 'deal_stages.lead_id', '=', 'leads.id')
            ->join('deals', 'deal_stages.lead_id', '=', 'deals.lead_id')
            ->join('p_m_projects', 'deals.id', '=', 'p_m_projects.deal_id')
            ->where('leads.added_by', $salesId)
            ->where('leads.project_type', 'hourly')
            ->where('leads.created_at', '>=', $startDate)
            ->where('leads.created_at', '<', $endDate)
            ->where('deal_stages.created_at', '>=', $startDate)
            ->where('deal_stages.created_at', '<', $endDate)
            ->where('deals.created_at', '>=', $startDate)
            ->where('deals.created_at', '<', $endDate)
            ->count();

        $this->number_of_leads_convert_won_deals = DB::table('deal_stages')
            ->join('leads', 'deal_stages.lead_id', '=', 'leads.id')
            ->join('deals', 'deal_stages.lead_id', '=', 'deals.lead_id')
            ->join('p_m_projects', 'deals.id', '=', 'p_m_projects.deal_id')
            ->where('leads.added_by', $salesId)
            ->where('leads.created_at', '>=', $startDate)
            ->where('leads.created_at', '<', $endDate)
            ->where('deal_stages.created_at', '>=', $startDate)
            ->where('deal_stages.created_at', '<', $endDate)
            ->where('deals.created_at', '>=', $startDate)
            ->where('deals.created_at', '<', $endDate)
            ->count();

        //--------------     won deals  table     --------------------------//

        $this->number_of_leads_convert_won_deals_data = DB::table('deal_stages')
            ->select('deals.*','leads.created_at as lead_created')
            ->join('leads', 'deal_stages.lead_id', '=', 'leads.id')
            ->join('deals', 'deal_stages.lead_id', '=', 'deals.lead_id')
            ->join('p_m_projects', 'deals.id', '=', 'p_m_projects.deal_id')
            ->where('leads.added_by', $salesId)
            ->where('leads.created_at', '>=', $startDate)
            ->where('leads.created_at', '<', $endDate)
            ->where('deal_stages.created_at', '>=', $startDate)
            ->where('deal_stages.created_at', '<', $endDate)
            ->where('deals.created_at', '>=', $startDate)
            ->where('deals.created_at', '<', $endDate)
            ->get();

        // average bidding 
        $number_of_leads_create_data = DB::table('leads')
        ->select('leads.*')
        ->where('added_by', $salesId)
        ->where('created_at', '>=', $startDate)
        ->where('created_at', '<', $endDate)
        ->get();
        $this->number_of_leads_create_data= $number_of_leads_create_data;

        $number_of_leads_create = DB::table('leads')
        ->where('added_by', $salesId)
        ->where('created_at', '>=', $startDate)
        ->where('created_at', '<', $endDate)
        ->count();
        
        $number_of_leads_amount = DB::table('leads')
        ->where('added_by', $salesId)
        ->where('created_at', '>=', $startDate)
        ->where('created_at', '<', $endDate)
        ->sum('value');

        if($number_of_leads_create>0){
            $this->average_number_of_leads_amount = $number_of_leads_amount / $number_of_leads_create;
        }else{
            $this->average_number_of_leads_amount = 0;
        }
        


        // average bidding delay time 

        $number_of_leads_create = DB::table('leads')
        ->where('added_by', $salesId)
        ->where('created_at', '>=', $startDate)
        ->where('created_at', '<', $endDate)
        ->count();

        $delay_minute = DB::table('leads')
        ->where('added_by', $salesId)
        ->where('created_at', '>=', $startDate)
        ->where('created_at', '<', $endDate)
        ->sum('bidding_minutes');

        $delay_second = DB::table('leads')
        ->where('added_by', $salesId)
        ->where('created_at', '>=', $startDate)
        ->where('created_at', '<', $endDate)
        ->sum('bidding_seconds');

        if ($number_of_leads_create > 0) {
          $this->average_bidding_delay_time= (($delay_minute *60+ $delay_second)/ $number_of_leads_create)/60;
       }else{
          $this->average_bidding_delay_time=0;

      }

       // ---------------bidding frequency--------------------------------------//  
       $total_minutes=0;
       $freqency_count=0;
        $attendence_data_by_user = DB::table('attendances')
        ->select('clock_in_time','clock_out_time')
        ->where('user_id', $salesId)
        ->where('created_at', '>=', $startDate)
        ->where('created_at', '<', $endDate)
        ->get();

        foreach($attendence_data_by_user as $attendence){
           
           if($attendence->clock_out_time != Null){
             $lead_generate = DB::table('leads')
                ->select('created_at')
                ->where('user_id', $salesId)
                ->where('created_at', '>=', $attendence->clock_in_time)
                ->where('created_at', '<', $attendence->clock_out_time)
                ->orderBy('created_at')
                ->get();

             $flag=0;
             $startTime='';
            foreach( $lead_generate as $lead){
                
                if($flag==0){
                        $flag=1;
                        $startTime = Carbon::parse($lead->created_at);
                    
                }else{
                        $endTime = Carbon::parse($lead->created_at);
                        $minutesDifference = $endTime->diffInMinutes($startTime);
                        $startTime= $endTime;
                        $total_minutes+= $minutesDifference;
                        $freqency_count++;

                }

            }
           }
        }

        if($freqency_count>0){
            $this->bidding_frequency = $total_minutes / $freqency_count;
        }else{
            $this->bidding_frequency =0;

        }

        //Country wise bidding breakdown


        $number_of_leads_received = DB::table('leads')
            ->where('added_by', $salesId)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate)
            ->count();

        $country_wise_lead_counts = DB::table('leads')
        ->select('country', DB::raw('COUNT(*) as lead_count'))
        ->where('created_at', '>=', $startDate)
        ->where('created_at', '<', $endDate)
        ->where('added_by', $salesId)
        ->groupBy( 'country')
        ->orderBy('lead_count','DESC')
        ->get();

        foreach($country_wise_lead_counts as $leads){
             
            $leads_percentage= ($leads->lead_count/ $number_of_leads_received )*100;

            $leads->leads_percentage= $leads_percentage;
        }
        $this->country_wise_lead_counts= $country_wise_lead_counts;

        // --------------Number of won deals (fixed)-------------//

        $number_of_leads_convert_won_deals = DB::table('deals')
        ->select('deals.*')
        ->where('deals.project_type', 'fixed')
        ->where('deals.created_at', '>=', $startDate)
        ->where('deals.created_at', '<', $endDate)
        ->get();

        $won_deals_count=0;
        $won_deals_value = 0;
        
        foreach($number_of_leads_convert_won_deals as $won_deals){

            //closing the deal

            if($won_deals->added_by== $salesId){

                $won_deals_count += .125;
                $won_deals_value += .125*$won_deals->amount;
            }


            //The bidder

            $leads = DB::table('leads')
                ->where('added_by', $salesId)
                ->where('id', $won_deals->lead_id)
                ->count();
            
            if($leads >0){

                $won_deals_count+= .25;
                $won_deals_value += .25 * $won_deals->amount;

            }

            //Qualifying

            $qualify_contribution = DB::table('deal_stage_changes')
                ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id',1)
                ->count();

            $qualify = DB::table('deal_stage_changes')
                ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 1)
                ->count();
            
            if($qualify>0){
               $won_deals_count+= .0375/ $qualify_contribution;
               $won_deals_value += .0375 / $qualify_contribution * $won_deals->amount;

            }


            //Needs Defined

            $needs_defined_contribution = DB::table('deal_stage_changes')
                ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 2)
                ->count();

            $needs_defined = DB::table('deal_stage_changes')
                ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 2)
                ->count();

            if ($needs_defined > 0) {
                $won_deals_count += .1875 / $needs_defined_contribution;
                $won_deals_value += .1875 / $needs_defined_contribution * $won_deals->amount;
            }

            //Proposal made


            $proposal_made_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
            ->where('deal_stage_id', 3)
                ->count();

            $proposal_made = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
            ->where('updated_by', $salesId)
                ->where('deal_stage_id', 3)
                ->count();

            if ($proposal_made > 0) {
                $won_deals_count += .125 / $proposal_made_contribution;
                $won_deals_value += .125 / $proposal_made_contribution * $won_deals->amount;
            }

            //Negotiation started

            $negotiation_contribution = DB::table('deal_stage_changes')
                ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 4)
                ->count();

            $negotiation = DB::table('deal_stage_changes')
                ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 4)
                ->count();

            if ($negotiation > 0) {
                $won_deals_count += .125 / $negotiation_contribution;
                $won_deals_value += .125 / $negotiation_contribution * $won_deals->amount;
            }

            //Sharing milestone breakdown

            $milestone_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
            ->where('deal_stage_id', 5)
            ->count();

            $milestone = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
            ->where('updated_by', $salesId)
            ->where('deal_stage_id', 5)
            ->count();

            if ($milestone > 0) {
                $won_deals_count += .15 / $milestone_contribution;
                $won_deals_value += .15 / $milestone_contribution * $won_deals->amount;
            }          

        }

        $this->won_deals_count_fixed =$won_deals_count;
        $this->won_deals_value_fixed = $won_deals_value;


        // --------------Number of won deals (hourly)-------------//

        $number_of_leads_convert_won_deals = DB::table('deals')
        ->select('deals.*')
        ->where('deals.project_type', 'hourly')
        ->where('deals.created_at', '>=', $startDate)
        ->where('deals.created_at', '<', $endDate)
        ->get();

        $won_deals_count = 0;
        $won_deals_value = 0;

        foreach ($number_of_leads_convert_won_deals as $won_deals) {

            //closing the deal

            if ($won_deals->added_by == $salesId) {

                $won_deals_count += .125;
                $won_deals_value +=  $won_deals->hourly_rate;
            }


            //The bidder

            $leads = DB::table('leads')
                ->where('added_by', $salesId)
                ->where('id', $won_deals->lead_id)
                ->count();

            if ($leads > 0) {

                $won_deals_count += .25;
                $won_deals_value += .25 * $won_deals->hourly_rate;
            }

            //Qualifying

            $qualify_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 1)
                ->count();

            $qualify = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 1)
                ->count();

            if ($qualify > 0) {
                $won_deals_count += .0375 / $qualify_contribution;
                $won_deals_value += .0375 / $qualify_contribution * $won_deals->hourly_rate;
            }


            //Needs Defined

            $needs_defined_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 2)
                ->count();

            $needs_defined = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 2)
                ->count();

            if ($needs_defined > 0) {
                $won_deals_count += .1875 / $needs_defined_contribution;
                $won_deals_value += .1875 / $needs_defined_contribution * $won_deals->hourly_rate;
            }

            //Proposal made


            $proposal_made_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 3)
                ->count();

            $proposal_made = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 3)
                ->count();

            if ($proposal_made > 0) {
                $won_deals_count += .125 / $proposal_made_contribution;
                $won_deals_value += .125 / $proposal_made_contribution * $won_deals->hourly_rate;
            }

            //Negotiation started

            $negotiation_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 4)
                ->count();

            $negotiation = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 4)
                ->count();

            if ($negotiation > 0) {
                $won_deals_count += .125 / $negotiation_contribution;
                $won_deals_value += .125 / $negotiation_contribution * $won_deals->hourly_rate;
            }

            //Sharing milestone breakdown

            $milestone_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 5)
                ->count();

            $milestone = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 5)
                ->count();

            if ($milestone > 0) {
                $won_deals_count += .15 / $milestone_contribution;
                $won_deals_value += .15 / $milestone_contribution * $won_deals->hourly_rate;
            }
        }

        $this->won_deals_count_hourly = $won_deals_count;
        $this->won_deals_value_hourly = $won_deals_value;


        // --------------Number of won deals (Country wise)-------------//

      $leads_country_data= DB::table('leads')
            ->select('country')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate)
            ->distinct('country')
            ->get();

     foreach($leads_country_data as $lead_country){

        $number_of_leads_convert_won_deals = DB::table('deals')
        ->select('deals.*')
        ->join('leads', 'deals.lead_id', '=', 'leads.id')
        ->where('leads.country', $lead_country->country )
        ->where('deals.created_at', '>=', $startDate)
        ->where('deals.created_at', '<', $endDate)
        ->get();

        $won_deals_count = 0;
        $won_deals_value = 0;

        foreach ($number_of_leads_convert_won_deals as $won_deals) {

            //closing the deal

            if ($won_deals->added_by == $salesId) {

                $won_deals_count += .125;
                if($won_deals->project_type=='fixed'){

                        $won_deals_value += .125 * $won_deals->amount;
                }else{

                        $won_deals_value += .125 * $won_deals->hourly_rate;

                }
               
            }

            //The bidder

            $leads = DB::table('leads')
                ->where('added_by', $salesId)
                ->where('id', $won_deals->lead_id)
                ->count();

            if ($leads > 0) {

                   $won_deals_count += .25;
                
                    if ($won_deals->project_type == 'fixed') {

                        $won_deals_value += .25 * $won_deals->amount;
                    } else {

                        $won_deals_value += .25 * $won_deals->hourly_rate;
                    }
            }

            //Qualifying

            $qualify_contribution = DB::table('deal_stage_changes')
               ->where('deal_id', $won_deals->lead_id)
                ->where('deal_stage_id', 1)
                ->count();

            $qualify = DB::table('deal_stage_changes')
                ->where('deal_id', $won_deals->lead_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 1)
                ->count();

            if ($qualify > 0) {
                   $won_deals_count += .0375 / $qualify_contribution;
                
                    if ($won_deals->project_type == 'fixed') {

                        $won_deals_value += .0375 / $qualify_contribution * $won_deals->amount;
                    } else {

                        $won_deals_value += .0375 / $qualify_contribution * $won_deals->hourly_rate;
                    }
            }


            //Needs Defined

            $needs_defined_contribution = DB::table('deal_stage_changes')
                ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 2)
                ->count();

            $needs_defined = DB::table('deal_stage_changes')
                ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 2)
                ->count();

            if ($needs_defined > 0) {
                $won_deals_count += .1875 / $needs_defined_contribution;
               
                    if ($won_deals->project_type == 'fixed') {

                        $won_deals_value += .1875 / $needs_defined_contribution * $won_deals->amount;
                    } else {

                        $won_deals_value += .1875 / $needs_defined_contribution * $won_deals->hourly_rate;
                    }
            }

            //Proposal made

            $proposal_made_contribution = DB::table('deal_stage_changes')
                ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 3)
                ->count();

            $proposal_made = DB::table('deal_stage_changes')
                ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 3)
                ->count();

            if ($proposal_made > 0) {
                $won_deals_count += .125 / $proposal_made_contribution;
              
                    if ($won_deals->project_type == 'fixed') {

                        $won_deals_value += .125 / $proposal_made_contribution * $won_deals->amount;
                    } else {

                        $won_deals_value += .125 / $proposal_made_contribution * $won_deals->hourly_rate;
                    }
            }

            //Negotiation started

            $negotiation_contribution = DB::table('deal_stage_changes')
                 ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 4)
                ->count();

            $negotiation = DB::table('deal_stage_changes')
                ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 4)
                ->count();

            if ($negotiation > 0) {
                $won_deals_count += .125 / $negotiation_contribution;
               

                    if ($won_deals->project_type == 'fixed') {

                        $won_deals_value += .125 / $negotiation_contribution * $won_deals->amount;
                    } else {

                        $won_deals_value += .125 / $negotiation_contribution * $won_deals->hourly_rate;
                    }
            }

            //Sharing milestone breakdown

            $milestone_contribution = DB::table('deal_stage_changes')
                ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 5)
                ->count();

            $milestone = DB::table('deal_stage_changes')
                ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 5)
                ->count();

            if ($milestone > 0) {
                $won_deals_count += .15 / $milestone_contribution;
               

                    if ($won_deals->project_type == 'fixed') {

                        $won_deals_value += .15 / $milestone_contribution * $won_deals->amount;
                    } else {

                        $won_deals_value += .15 / $milestone_contribution * $won_deals->hourly_rate;
                    }
            }
        }
        
                $lead_country->won_deals_count= $won_deals_count;
                $lead_country->won_deals_value= $won_deals_value;

                $won_deals_count = 0;
                $won_deals_value = 0;

    }

       $sort_leads_country_data= $leads_country_data->sortByDesc('won_deals_value');

       $this->leads_country_data = $sort_leads_country_data;

        //---------------Project completion/Won deal ratio-----------------------------------//


        $this->project_completion_count = DB::table('contracts')
            ->join('projects', 'contracts.deal_id', '=', 'projects.deal_id')
            ->where('projects.status','finished')
            ->where('contracts.last_updated_by', $salesId)
            ->where('contracts.updated_at', '<', $endDate)
            ->count();

        $this->project_canceled_count = DB::table('contracts')
            ->join('projects', 'contracts.deal_id', '=', 'projects.deal_id')
            ->whereIn('projects.status', ['canceled', 'partially finished'])
            ->where('contracts.last_updated_by', $salesId)
            ->where('contracts.updated_at', '<', $endDate)
            ->count();

        $this->project_reject_count = DB::table('contracts')
            ->join('projects', 'contracts.deal_id', '=', 'projects.deal_id')
            ->where('projects.project_status', 'Not Accepted')
            ->where('contracts.last_updated_by', $salesId)
            ->where('contracts.updated_at', '<', $endDate)
            ->count();

        $this->won_deal_count = DB::table('contracts')
            ->where('last_updated_by', $salesId)
            ->where('updated_at', '<', $exclude_end_date)
            ->count();

        // exclude one for end cycle date and other for today.  suppose today december 20  and end cycle 31. but logic will be  20-exclude date for current month 

        $this->won_deal_count_reject_project = DB::table('contracts')
            ->where('last_updated_by', $salesId)
            ->where('updated_at', '<', $endDate)
            ->count();
            
        // same logic for end date for current month
        if($this->won_deal_count > 0){
            $this->project_completion_count_ratio = ($this->project_completion_count / $this->won_deal_count) * 100;
        }else {
            $this->project_completion_count_ratio =0;
        }

        if ($this->won_deal_count > 0) {
            $this->project_canceled_count_ratio = ($this->project_canceled_count  / $this->won_deal_count) * 100;
        } else {
            $this->project_canceled_count_ratio = 0;
        }


        if ($this->won_deal_count_reject_project > 0) {
            $this->project_reject_count_ratio = ($this->project_reject_count / $this->won_deal_count_reject_project) * 100;
        } else {
            $this->project_reject_count_ratio = 0;
        }
      
       

        //--------Reject Projects Count---------------//

        $this->project_reject_count_data = DB::table('contracts')
           ->select('projects.*','contracts.last_updated_by')
           ->join('projects', 'contracts.deal_id', '=', 'projects.deal_id')
           ->where('projects.project_status', 'Not Accepted')
           ->where('contracts.last_updated_by', $salesId)
           ->where('contracts.updated_at', '<', $endDate)
           ->get();

        // --------------Number of won deals table (fixed)-------------//

        $number_of_leads_convert_won_deals_table = DB::table('deals')
        ->select('deals.*')
        ->where('deals.project_type', 'fixed')
        ->where('deals.created_at', '>=', $startDate)
        ->where('deals.created_at', '<', $endDate)
        ->get();

        $won_deals_count = 0;
        $won_deals_value = 0;
        $yes='YES';
        $no='NO';

        foreach ($number_of_leads_convert_won_deals_table as $won_deals) {

            //closing the deal

            if ($won_deals->added_by == $salesId) {

                $won_deals_count += .125;
                $won_deals_value += .125 * $won_deals->amount;

                $won_deals->closing_deal= $yes;
            

            }else{

                $won_deals->closing_deal = $no;
            }


            //The bidder

            $leads = DB::table('leads')
                ->where('added_by', $salesId)
                ->where('id', $won_deals->lead_id)
                ->count();

            if ($leads > 0) {

                $won_deals_count += .25;
                $won_deals_value += .25 * $won_deals->amount;
                $won_deals->bidder = $yes;
            } else {

                $won_deals->bidder = $no;
            }

            //Qualifying

            $qualify_contribution = DB::table('deal_stage_changes')
              ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 1)
                ->distinct('updated_by')
                ->count();

            $qualify = DB::table('deal_stage_changes')
                ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 1)
                ->count();

            if ($qualify > 0 && $qualify_contribution >0 ) {
                $won_deals_count += .0375 / $qualify_contribution;
                $won_deals_value += .0375 / $qualify_contribution * $won_deals->amount;
                $won_deals->qualifying = $yes;
            } else {

                $won_deals->qualifying = $no;
            }

            //Needs Defined

            $needs_defined_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 2)
                ->distinct('updated_by')
                ->count();

            $needs_defined = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 2)
                ->count();

            if ($needs_defined > 0 && $needs_defined_contribution>0) {
                $won_deals_count += .1875 / $needs_defined_contribution;
                $won_deals_value += .1875 / $needs_defined_contribution * $won_deals->amount;

                $won_deals->needs_defined = $yes;
            } else {

                $won_deals->needs_defined = $no;
            }

            //Proposal made


            $proposal_made_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 3)
                ->distinct('updated_by')
                ->count();

            $proposal_made = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 3)
                ->count();

            if ($proposal_made > 0 && $proposal_made_contribution>0) {
                $won_deals_count += .125 / $proposal_made_contribution;
                $won_deals_value += .125 / $proposal_made_contribution * $won_deals->amount;
                $won_deals->proposal_made = $yes;
            } else {

                $won_deals->proposal_made = $no;
            }

            //Negotiation started

            $negotiation_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 4)
                ->distinct('updated_by')
                ->count();

            $negotiation = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 4)
                ->count();

            if ($negotiation > 0 && $negotiation_contribution>0) {
                $won_deals_count += .125 / $negotiation_contribution;
                $won_deals_value += .125 / $negotiation_contribution * $won_deals->amount;
                $won_deals->negotiation_started = $yes;
            } else {

                $won_deals->negotiation_started = $no;
            }

            //Sharing milestone breakdown

            $milestone_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 5)
                ->distinct('updated_by')
                ->count();

            $milestone = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 5)
                ->count();

            if ($milestone > 0 && $milestone_contribution>0) {
                $won_deals_count += .15 / $milestone_contribution;
                $won_deals_value += .15 / $milestone_contribution * $won_deals->amount;
                $won_deals->sharing_milestone_breakdown = $yes;
            } else {

                $won_deals->sharing_milestone_breakdown = $no;
            }

            $won_deals->deals_each_count = $won_deals_count;
            $won_deals->deals_each_value = $won_deals_value;

            $won_deals_count = 0;
            $won_deals_value = 0;

        }

        $this->number_of_leads_convert_won_deals_table= $number_of_leads_convert_won_deals_table;

        // --------------Number of won deals table (hourly)-------------//

        $number_of_leads_convert_won_deals_table_hourly = DB::table('deals')
        ->select('deals.*')
        ->where('deals.project_type', 'hourly')
        ->where('deals.created_at', '>=', $startDate)
        ->where('deals.created_at', '<', $endDate)
        ->get();

        $won_deals_count = 0;
        $won_deals_value = 0;
        $yes = 'YES';
        $no = 'NO';

        foreach ($number_of_leads_convert_won_deals_table_hourly as $won_deals) {

            //closing the deal

            if ($won_deals->added_by == $salesId) {

                $won_deals_count += .125;
                $won_deals_value += .125 * $won_deals->hourly_rate;

                $won_deals->closing_deal = $yes;
            } else {

                $won_deals->closing_deal = $no;
            }


            //The bidder

            $leads = DB::table('leads')
                ->where('added_by', $salesId)
                ->where('id', $won_deals->lead_id)
                ->count();

            if ($leads > 0) {

                $won_deals_count += .25;
                $won_deals_value += .25 * $won_deals->hourly_rate;
                $won_deals->bidder = $yes;
            } else {

                $won_deals->bidder = $no;
            }

            //Qualifying

            $qualify_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 1)
                ->distinct('updated_by')
                ->count();

            $qualify = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 1)
                ->count();

            if ($qualify > 0 && $qualify_contribution > 0) {
                $won_deals_count += .0375 / $qualify_contribution;
                $won_deals_value += .0375 / $qualify_contribution * $won_deals->hourly_rate;
                $won_deals->qualifying = $yes;
            } else {

                $won_deals->qualifying = $no;
            }

            //Needs Defined

            $needs_defined_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 2)
                ->distinct('updated_by')
                ->count();

            $needs_defined = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 2)
                ->count();

            if ($needs_defined > 0 && $needs_defined_contribution > 0) {
                $won_deals_count += .1875 / $needs_defined_contribution;
                $won_deals_value += .1875 / $needs_defined_contribution * $won_deals->hourly_rate;

                $won_deals->needs_defined = $yes;
            } else {

                $won_deals->needs_defined = $no;
            }

            //Proposal made


            $proposal_made_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 3)
                ->distinct('updated_by')
                ->count();

            $proposal_made = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 3)
                ->count();

            if ($proposal_made > 0 && $proposal_made_contribution > 0) {
                $won_deals_count += .125 / $proposal_made_contribution;
                $won_deals_value += .125 / $proposal_made_contribution * $won_deals->hourly_rate;
                $won_deals->proposal_made = $yes;
            } else {

                $won_deals->proposal_made = $no;
            }

            //Negotiation started

            $negotiation_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 4)
                ->distinct('updated_by')
                ->count();

            $negotiation = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 4)
                ->count();

            if ($negotiation > 0 && $negotiation_contribution > 0) {
                $won_deals_count += .125 / $negotiation_contribution;
                $won_deals_value += .125 / $negotiation_contribution * $won_deals->hourly_rate;
                $won_deals->negotiation_started = $yes;
            } else {

                $won_deals->negotiation_started = $no;
            }

            //Sharing milestone breakdown

            $milestone_contribution = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('deal_stage_id', 5)
                ->distinct('updated_by')
                ->count();

            $milestone = DB::table('deal_stage_changes')
            ->where('deal_id', $won_deals->deal_id)
                ->where('updated_by', $salesId)
                ->where('deal_stage_id', 5)
                ->count();

            if ($milestone > 0 && $milestone_contribution > 0) {
                $won_deals_count += .15 / $milestone_contribution;
                $won_deals_value += .15 / $milestone_contribution * $won_deals->hourly_rate;
                $won_deals->sharing_milestone_breakdown = $yes;
            } else {

                $won_deals->sharing_milestone_breakdown = $no;
            }

            $won_deals->deals_each_count = $won_deals_count;
            $won_deals->deals_each_value = $won_deals_value;

            $won_deals_count = 0;
            $won_deals_value = 0;
        }

        $this->number_of_leads_convert_won_deals_table_hourly = $number_of_leads_convert_won_deals_table_hourly;



        return view('holiday.index', $this->data);

    }


    protected function googleCalendarEvent($event)
    {
        $module = GoogleCalendarModule::first();
        $googleAccount = global_setting();

        if ($googleAccount->google_calendar_status == 'active' && $googleAccount->google_calendar_verification_status == 'verified' && $googleAccount->token && $module->holiday_status == 1) {

            $google = new Google();

            $description = __('messages.invoiceDueOn');

            // Create event
            $google = $google->connectUsing($googleAccount->token);

            $eventData = new \Google_Service_Calendar_Event(array(
                'summary' => $event->occassion,
                'location' => $googleAccount->address,
                'description' => $description,
                'colorId' => 1,
                'start' => array(
                    'dateTime' => $event->date,
                    'timeZone' => $googleAccount->timezone,
                ),
                'end' => array(
                    'dateTime' => $event->date,
                    'timeZone' => $googleAccount->timezone,
                ),
                'reminders' => array(
                    'useDefault' => false,
                    'overrides' => array(
                        array('method' => 'email', 'minutes' => 24 * 60),
                        array('method' => 'popup', 'minutes' => 10),
                    ),
                ),
            ));

            try {
                if ($event->event_id) {
                    $results = $google->service('Calendar')->events->patch('primary', $event->event_id, $eventData);
                }
                else {
                    $results = $google->service('Calendar')->events->insert('primary', $eventData);
                }

                return $results->id;
            } catch (\Google\Service\Exception $error) {
                if(is_null($error->getErrors())) {
                    // Delete google calendar connection data i.e. token, name, google_id
                    $googleAccount->name = null;
                    $googleAccount->token = null;
                    $googleAccount->google_id = null;
                    $googleAccount->google_calendar_verification_status = 'non_verified';
                    $googleAccount->save();
                }
            }

        }

        return $event->event_id;
    }

    protected function googleCalendarEventMulti($day, $year, $days)
    {
        $googleAccount = global_setting();
        $module = GoogleCalendarModule::first();

        if ($googleAccount->google_calendar_status == 'active' && $googleAccount->google_calendar_verification_status == 'verified' && $googleAccount->token && $module->holiday_status == 1 )
        {
            $this->days = $days;
            $google = new Google();

            $allDays = $this->getDateForSpecificDayBetweenDates($year . '-01-01', $year . '-12-31', $day);

            $holiday = Holiday::where(DB::raw('DATE(`date`)'), $allDays[0])->first();

            $startDate = Carbon::parse($allDays[0]);

            $frequency = 'WEEKLY';

            $eventData = new \Google_Service_Calendar_Event();
            $eventData->setSummary($this->days[$day]);
            $eventData->setColorId(7);
            $eventData->setLocation('');

            $start = new \Google_Service_Calendar_EventDateTime();
            $start->setDateTime($startDate);
            $start->setTimeZone($googleAccount->timezone);

            $eventData->setStart($start);

            $end = new \Google_Service_Calendar_EventDateTime();
            $end->setDateTime($startDate);
            $end->setTimeZone($googleAccount->timezone);

            $eventData->setEnd($end);

            $dy = mb_strtoupper(substr($this->days[$day], 0, 2));

            $eventData->setRecurrence(array('RRULE:FREQ='.$frequency.';COUNT='.count($allDays).';BYDAY='.$dy));

            // Create event
            $google->connectUsing($googleAccount->token);
            // array for multiple

            try {
                if ($holiday->event_id) {
                    $results = $google->service('Calendar')->events->patch('primary', $holiday->event_id, $eventData);
                }
                else {
                    $results = $google->service('Calendar')->events->insert('primary', $eventData);
                }

                $holidays = Holiday::where('occassion', $this->days[$day])->get();

                foreach($holidays as $holiday){
                    $holiday->event_id = $results->id;
                    $holiday->save();
                }

                return;
            } catch (\Google\Service\Exception $error) {
                if(is_null($error->getErrors())) {
                    // Delete google calendar connection data i.e. token, name, google_id
                    $googleAccount->name = null;
                    $googleAccount->token = null;
                    $googleAccount->google_id = null;
                    $googleAccount->google_calendar_verification_status = 'non_verified';
                    $googleAccount->save();
                }
            }


            $holidays = Holiday::where('occassion', $this->days[$day])->get();

            foreach($holidays as $holiday){
                $holiday->event_id = $holiday->event_id;
                $holiday->save();
            }

            return;
        }
    }

}

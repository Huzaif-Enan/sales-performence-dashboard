@extends('layouts.app')

@push('datatable-styles')
    {{-- @include('sections.datatable_css')
    <style>
        .filter-box {
            z-index: 2;
        }

    </style> --}}
@endpush

@section('filter-section')
    {{-- <x-filters.filter-box>
        <!-- DATE START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-3 f-14 text-dark-grey d-flex align-items-center">@lang('app.month')</p>
            <div class="select-month">
                <select class="form-control select-picker" name="month" id="month" data-live-search="true" data-size="8">
                    @foreach ($months as $month)
                        <option @if ($currentMonth == $loop->iteration) selected @endif value="{{ $loop->iteration }}">{{ ucfirst($month) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <!-- MONTH END -->

        <!-- YEAR START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-3 f-14 text-dark-grey d-flex align-items-center">@lang('app.year')</p>
            <div class="select-year">
                <select class="form-control select-picker" name="year" id="year" data-live-search="true" data-size="8">
                    @foreach ($years as $year)
                        <option @if ($year == $currentYear) selected @endif
                            value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <!-- YEAR END -->

        <!-- SEARCH BY TASK START -->
        <div class="task-search d-flex  py-1 px-lg-3 px-0 border-right-grey align-items-center">
            <form class="w-100 mr-1 mr-lg-0 mr-md-1 ml-md-1 ml-0 ml-lg-0">
                <div class="input-group bg-grey rounded">
                    <div class="input-group-prepend">
                        <span class="input-group-text border-0 bg-additional-grey">
                            <i class="fa fa-search f-13 text-dark-grey"></i>
                        </span>
                    </div>
                    <input type="text" class="form-control f-14 p-1 border-additional-grey" id="search-text-field"
                        placeholder="@lang('app.startTyping')">
                </div>
            </form>
        </div>
        <!-- SEARCH BY TASK END -->

        <!-- RESET START -->
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>
        <!-- RESET END -->
    </x-filters.filter-box>
@endsection

@php
$addPermission = user()->permission('add_holiday');
@endphp

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <!-- Add Task Export Buttons Start -->
        <div class="d-block d-lg-flex d-md-flex action-bar justify-content-between ">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                @if ($addPermission == 'all' || $addPermission == 'added')
                    <x-forms.link-primary :link="route('holidays.create')" class="mr-3 openRightModal float-left mb-2 mb-lg-0 mb-md-0"
                        icon="plus">
                        @lang('modules.holiday.addNewHoliday')
                    </x-forms.link-primary>
                    <x-forms.button-secondary icon="check" class="mr-3 float-left mb-2 mb-lg-0 mb-md-0" id="mark-holiday">
                        @lang('modules.holiday.markSunday')
                    </x-forms.button-secondary>
                @endif
            </div>


            <x-datatable.actions>
                <div class="select-status mr-3 pl-3">
                    <select name="action_type" class="form-control select-picker" id="quick-action-type" disabled>
                        <option value="">@lang('app.selectAction')</option>
                        <option value="delete">@lang('app.delete')</option>
                    </select>
                </div>
            </x-datatable.actions>

            <div class="btn-group ml-3" role="group" aria-label="Basic example">
                <a href="{{ route('holidays.index') }}" class="btn btn-secondary f-14 btn-active" data-toggle="tooltip"
                    data-original-title="@lang('modules.leaves.tableView')"><i class="side-icon bi bi-list-ul"></i></a>

                <a href="{{ route('holidays.calendar') }}" class="btn btn-secondary f-14" data-toggle="tooltip"
                    data-original-title="@lang('app.menu.calendar')"><i class="side-icon bi bi-calendar"></i></a>
            </div>
        </div>

        <!-- holiday table Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">

            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}

        </div>
        <!-- leave table End -->

    </div>
    <!-- CONTENT WRAPPER END --> --}}
<div>
    @php
    $users= App\Models\User::where('role_id',7)->get();
    @endphp
    <div class="form-group">
        <form action="{{ route('holydays.sales_performence') }}"
              method="POST">
            {{ csrf_field() }}
            <label for="projectmanager">NAME:</label>
            <select name="salesID"
                    id="dropdown1"
                    required>
                @foreach($users as $user)
                <option value="{{$user->id}}">{{$user->name}}</option>
                @endforeach
            </select>
            <label for="start_date">Start Date:</label>
            <input type="date"
                   name="start_date"
                   id="start_date"
                   required>

            <label for="end_date">End Date:</label>
            <input type="date"
                   name="end_date"
                   id="end_date"
                   required>

            <button type="submit"
                    class="btn btn-primary">Submit</button>
        </form>
    </div>

@if(isset($username))
<h4> Name:{{$username}}</h4>
<h4 style="color: rgba(16, 47, 200, 0.777)">Date:{{ $startDate1->format('Y-m-d') }} to {{ $endDate1->format('Y-m-d') }} </h4>
<h4>Total Number of Leads : {{$number_of_leads_received}}</h4>
<h4>Total Number of Leads for fixed Project: {{$number_of_leads_received_fixed }}</h4>
<h4>Total Number of Leads for hourly Project: {{$number_of_leads_received_hourly }}</h4><br><br>
<h4>Total Number of Converted Deals: {{$number_of_leads_convert_deals}}</h4>
<h4>Total Number of Converted deals (Fixed) : {{$number_of_leads_convert_deals_fixed}}</h4>
<h4>Total Number of Converted deals (Hourly): {{$number_of_leads_convert_deals_hourly }}</h4><br><br>

<h4>Total Number of Converted Lead to Won Deals: {{$number_of_leads_convert_won_deals}}</h4>
<h4>Total Number of Converted Lead to Won deals (Fixed) : {{$number_of_leads_convert_won_deals_fixed}}</h4>
<h4>Total Number of Converted Lead to Won deals (Hourly): {{$number_of_leads_convert_won_deals_hourly }}</h4>
<br><br>

<h4>Average bidding amount (For leads) : {{round($average_number_of_leads_amount,2)}} Dollar</h4><br>

<h4>Average bidding delay time : {{round($average_bidding_delay_time,2)}} Minutes</h4><br>
<h4>Bidding frequency : {{round($bidding_frequency,2)}} Minutes</h4><br>

<h4>Won deals Count (Fixed) : {{round($won_deals_count_fixed,2)}} </h4>
<h4>Won deals Count (Hourly) : {{round($won_deals_count_hourly,2)}} </h4><br>

<h4>Won deals Value (Fixed) : {{round($won_deals_value_fixed,2)}} Dollar</h4>
<h4>Won deals Value (Hourly) : {{round($won_deals_value_hourly,2)}} Dollar</h4><br>

<h4>Average Deal Amount: {{round(($won_deals_value_fixed+$won_deals_value_hourly)/($won_deals_count_fixed+$won_deals_count_hourly),2)}} Dollar</h4><br>

 @if(isset($username))
 <h4>Developer Name:{{$username}}</h4><br>
 @endif
<h4>Project completion/Won deal ratio: {{round($project_completion_count_ratio,2)}} %</h4>
<h4>Canceled project count/won deal ratio: {{round($project_canceled_count_ratio,2)}} %</h4>
<h4>Reject project count/won deal ratio: {{round($project_reject_count_ratio,2)}} %</h4><br>

 @endif


 <div>

    <h3>Country wise Won Deals </h3>
    @if(isset($username))
    <h4>Developer Name:{{$username}}</h4>
    <h4 style="color: rgba(16, 47, 200, 0.777)">Date:{{ $startDate1->format('Y-m-d') }} to {{ $endDate1->format('Y-m-d') }} </h4>
    @endif
    <table class="table table-striped">
        <thead>
            <tr>
                <th>SL NO</th>
                <th>Country Name</th>
                <th> Won Deals Count</th>
                <th>Won Deals Value</th>
              
               

            </tr>

        </thead>
        <tbody> @if(isset($leads_country_data))
                @php
                $serial = 1;
                @endphp
            @foreach ($leads_country_data as $key => $row)
            @if($row->won_deals_count>0)
            <tr>
                <td>{{$serial++}}</td>
                <td>{{ $row->country }}</td>
                <td>{{ round($row->won_deals_count,2) }}</td>
                <td>{{ round($row->won_deals_value,2) }}</td>
                
            </tr>
            @endif
            @endforeach
            @endif
        </tbody>
    </table><br><br>
 </div>

 <div>

    <h3>Country wise Lead Count </h3>
    @if(isset($username))
    <h4>Developer Name:{{$username}}</h4>
    <h4 style="color: rgba(16, 47, 200, 0.777)">Date:{{ $startDate1->format('Y-m-d') }} to {{ $endDate1->format('Y-m-d') }} </h4>
    @endif
    <table class="table table-striped">
        <thead>
            <tr>
                <th>SL NO</th>
                <th>Country Name</th>
                <th>Total Leads</th>
                <th>Percentage</th>
              
               

            </tr>

        </thead>
        <tbody> @if(isset($country_wise_lead_counts))
            @foreach ($country_wise_lead_counts as $key => $row)
            <tr>
                <td>{{$key+1}}</td>
                <td>{{ $row->country }}</td>
                <td>{{ $row->lead_count }}</td>
                <td>{{ round($row->leads_percentage,2) }}</td>
                
            </tr>
            @endforeach
            @endif
        </tbody>
    </table><br><br>
 </div>

 <div>

    <h3>Rejected Project </h3>
    @if(isset($username))
    <h4>Developer Name:{{$username}}</h4>
    <h4 style="color: rgba(16, 47, 200, 0.777)">Date:{{ $startDate1->format('Y-m-d') }} to {{ $endDate1->format('Y-m-d') }} </h4>
    @endif
    <table class="table table-striped">
        <thead>
            <tr>
                <th>SL NO</th>
                <th>Project Name</th>
                <th>Project Budget</th>
                <th>Project Status</th>
            </tr>

        </thead>
        <tbody> @if(isset($project_reject_count_data ))
            @foreach ($project_reject_count_data as $key => $row)
            <tr>
                <td>{{$key+1}}</td>
                <td>{{ $row->project_name }}</td>
                <td>{{ $row->project_budget }}</td>
                <td>{{ $row->project_status }}</td>
                      
            </tr>
            @endforeach
            @endif
        </tbody>
    </table><br><br>
 </div>


 <div>


    <div>

    <h3>Number of Leads Converted into Won Deals </h3>
    @if(isset($username))
    <h4>Developer Name:{{$username}}</h4>
    <h4 style="color: rgba(16, 47, 200, 0.777)">Date:{{ $startDate1->format('Y-m-d') }} to {{ $endDate1->format('Y-m-d') }} </h4>
    @endif
    <table class="table table-striped">
        <thead>
            <tr>
                <th>SL NO</th>
                <th>Project Name</th>
                <th>Project Budget</th>
                <th>Lead Creation Date</th>
                <th>Won Deal Date</th>
            </tr>

        </thead>
        <tbody> @if(isset($number_of_leads_convert_won_deals_data ))
            @foreach ($number_of_leads_convert_won_deals_data as $key => $row)
            <tr>
                <td>{{$key+1}}</td>
                <td>{{ $row->project_name }}</td>
                <td>{{ round($row->amount,2) }}</td>
                <td>{{ $row->lead_created }}</td>
                <td>{{ $row->created_at }}</td>
                      
            </tr>
            @endforeach
            @endif
        </tbody>
    </table><br><br>
 </div>

 
    <h3>Leads Creation </h3>
    @if(isset($username))
    <h4>Developer Name:{{$username}}</h4>
    <h4 style="color: rgba(16, 47, 200, 0.777)">Date:{{ $startDate1->format('Y-m-d') }} to {{ $endDate1->format('Y-m-d') }} </h4>
    @endif
    <table class="table table-striped">
        <thead>
            <tr>
                <th>SL NO</th>
                <th>Project Name</th>
                <th>Bidding Value</th>
                <th>Lead Creation Date</th>
     
            </tr>

        </thead>
        <tbody> @if(isset($number_of_leads_create_data  ))
            @foreach ($number_of_leads_create_data  as $key => $row)
            <tr>
                <td>{{$key+1}}</td>
                <td>{{ $row->client_name }}</td>
                <td>{{ round($row->value,2) }}</td>
                <td>{{ $row->created_at }}</td>
                      
            </tr>
            @endforeach
            @endif
        </tbody>
    </table><br><br>
 </div>


 <div>

    <h3>Won Deals Contribution(Fixed) </h3>
    @if(isset($username))
    <h4>Developer Name:{{$username}}</h4>
    <h4 style="color: rgba(16, 47, 200, 0.777)">Date:{{ $startDate1->format('Y-m-d') }} to {{ $endDate1->format('Y-m-d') }} </h4>
    @endif
    <table class="table table-striped">
        <thead>
            <tr>
                <th>SL NO</th>
                <th>Project Name</th>
                <th>Project Budget</th>
                <th>Bidder</th>
                <th>Qualifying</th>
                <th>Needs Defined</th>
                <th>Proposal Made</th>
                <th>Negotiation Started</th>
                <th>Sharing Milestone Breakdown</th>
                <th>Closing The Deal</th>
                <th>Total Count</th>
                <th>Total Value</th>

            </tr>

        </thead>
        <tbody> @if(isset($number_of_leads_convert_won_deals_table ))
                @php
                $serial = 1;
                @endphp
            @foreach ($number_of_leads_convert_won_deals_table as $key => $row)
            @if($row->deals_each_count>0)
            <tr>
                <td>{{$serial++}}</td>
                <td>{{ $row->project_name }}</td>
                <td>{{ round($row->amount,2) }}</td>
                <td style="color: {{ $row->bidder == 'YES' ? 'green' : 'red' }}">{{ $row->bidder }}</td>
                <td style="color: {{ $row->qualifying == 'YES' ? 'green' : 'red' }}">{{ $row->qualifying }}</td>
                <td style="color: {{ $row->needs_defined == 'YES' ? 'green' : 'red' }}">{{ $row->needs_defined }}</td>
                <td style="color: {{ $row->proposal_made == 'YES' ? 'green' : 'red' }}">{{ $row->proposal_made }}</td>
                <td style="color: {{ $row->negotiation_started == 'YES' ? 'green' : 'red' }}">{{ $row->negotiation_started }}</td>
                <td style="color: {{ $row->sharing_milestone_breakdown == 'YES' ? 'green' : 'red' }}">{{ $row->sharing_milestone_breakdown }}</td>
                <td style="color: {{ $row->closing_deal == 'YES' ? 'green' : 'red' }}">{{ $row->closing_deal }}</td>
                <td>{{ round($row->deals_each_count,3) }}</td>
                <td>{{ round($row->deals_each_value,2) }}</td>
                
            </tr>
            @endif
            @endforeach
            @endif
        </tbody>
    </table><br><br>

 </div>


  <div>

    <h3>Won Deals Contribution(Hourly) </h3>
    @if(isset($username))
    <h4>Developer Name:{{$username}}</h4>
    <h4 style="color: rgba(16, 47, 200, 0.777)">Date:{{ $startDate1->format('Y-m-d') }} to {{ $endDate1->format('Y-m-d') }} </h4>
    @endif
    <table class="table table-striped">
        <thead>
            <tr>
                <th>SL NO</th>
                <th>Project Name</th>
                <th>Hourly Rate</th>
                <th>Bidder</th>
                <th>Qualifying</th>
                <th>Needs Defined</th>
                <th>Proposal Made</th>
                <th>Negotiation Started</th>
                <th>Sharing Milestone Breakdown</th>
                <th>Closing The Deal</th>
                <th>Total Count</th>
                <th>Total Value</th>

            </tr>

        </thead>
        <tbody> @if(isset($number_of_leads_convert_won_deals_table_hourly ))
                @php
                $serial = 1;
                @endphp
            @foreach ($number_of_leads_convert_won_deals_table_hourly as $key => $row)
            @if($row->deals_each_count>0)
            <tr>
                <td>{{$serial++}}</td>
                <td>{{ $row->project_name }}</td>
                <td>{{ round($row->hourly_rate,2) }}</td>
                <td style="color: {{ $row->bidder == 'YES' ? 'green' : 'red' }}">{{ $row->bidder }}</td>
                <td style="color: {{ $row->qualifying == 'YES' ? 'green' : 'red' }}">{{ $row->qualifying }}</td>
                <td style="color: {{ $row->needs_defined == 'YES' ? 'green' : 'red' }}">{{ $row->needs_defined }}</td>
                <td style="color: {{ $row->proposal_made == 'YES' ? 'green' : 'red' }}">{{ $row->proposal_made }}</td>
                <td style="color: {{ $row->negotiation_started == 'YES' ? 'green' : 'red' }}">{{ $row->negotiation_started }}</td>
                <td style="color: {{ $row->sharing_milestone_breakdown == 'YES' ? 'green' : 'red' }}">{{ $row->sharing_milestone_breakdown }}</td>
                <td style="color: {{ $row->closing_deal == 'YES' ? 'green' : 'red' }}">{{ $row->closing_deal }}</td>
                <td>{{ round($row->deals_each_count,3) }}</td>
                <td>{{ round($row->deals_each_value,2) }}</td>
                
            </tr>
            @endif
            @endforeach
            @endif
        </tbody>
    </table><br><br>

 </div>

@endsection

@push('scripts')

    {{-- @include('sections.datatable_js')

    <script>
        $('#holiday-table').on('preXhr.dt', function(e, settings, data) {
            var month = $('#month').val();
            var year = $('#year').val();
            var searchText = $('#search-text-field').val();

            data['month'] = month;
            data['year'] = year;
            data['searchText'] = searchText;
        });

        const showTable = () => {
            window.LaravelDataTables["holiday-table"].draw();
        }

        $('#search-text-field, #month, #year').on('change keyup',
            function() {
                if ($('#month').val() != "") {
                    $('#reset-filters').removeClass('d-none');
                    showTable();
                } else if ($('#year').val() != "") {
                    $('#reset-filters').removeClass('d-none');
                    showTable();
                } else if ($('#search-text-field').val() != "") {
                    $('#reset-filters').removeClass('d-none');
                    showTable();
                } else {
                    $('#reset-filters').addClass('d-none');
                    showTable();
                }
            });

        $('#reset-filters').click(function() {
            $('#filter-form')[0].reset();
            $('#month').val('{{ $currentMonth }}');
            $('#year').val('{{ $currentYear }}');
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('#quick-action-type').change(function() {
            const actionValue = $(this).val();

            if (actionValue != '') {
                $('#quick-action-apply').removeAttr('disabled');
            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
            }
        });

        $('#quick-action-apply').click(function() {
            const actionValue = $('#quick-action-type').val();
            if (actionValue == 'delete') {
                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    text: "@lang('messages.recoverRecord')",
                    icon: 'warning',
                    showCancelButton: true,
                    focusConfirm: false,
                    confirmButtonText: "@lang('messages.confirmDelete')",
                    cancelButtonText: "@lang('app.cancel')",
                    customClass: {
                        confirmButton: 'btn btn-primary mr-3',
                        cancelButton: 'btn btn-secondary'
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        applyQuickAction();
                    }
                });

            } else {
                applyQuickAction();
            }
        });

        $('body').on('click', '.delete-table-row', function() {
            var id = $(this).data('holiday-id');
            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('messages.recoverRecord')",
                icon: 'warning',
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "@lang('messages.confirmDelete')",
                cancelButtonText: "@lang('app.cancel')",
                customClass: {
                    confirmButton: 'btn btn-primary mr-3',
                    cancelButton: 'btn btn-secondary'
                },
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = "{{ route('holidays.destroy', ':id') }}";
                    url = url.replace(':id', id);

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {
                            '_token': token,
                            '_method': 'DELETE'
                        },
                        success: function(response) {
                            if (response.status == "success") {
                                showTable();
                            }
                        }
                    });
                }
            });
        });

        const applyQuickAction = () => {
            var rowdIds = $("#holiday-table input:checkbox:checked").map(function() {
                return $(this).val();
            }).get();

            var url = "{{ route('holidays.apply_quick_action') }}?row_ids=" + rowdIds;

            $.easyAjax({
                url: url,
                container: '#quick-action-form',
                type: "POST",
                disableButton: true,
                buttonSelector: "#quick-action-apply",
                data: $('#quick-action-form').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        showTable();
                        resetActionButtons();
                        deSelectAll();
                    }
                }
            })
        };

        $('body').on('click', '.show-holiday', function() {
            var holidayId = $(this).data('holiday-id');

            var url = '{{ route('holidays.show', ':id') }}';
            url = url.replace(':id', holidayId);

            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('body').on('click', '#mark-holiday', function() {
            var url = "{{ route('holidays.mark_holiday') }}?year" + $('#year').val();

            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });
    </script> --}}
@endpush

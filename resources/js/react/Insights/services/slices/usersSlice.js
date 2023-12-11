


import { createSlice }  from '@reduxjs/toolkit';



const initialState = {
    users: [],
    usersObjects: null,
}


const usersSlice = createSlice({
    name: 'users',
    initialState,
    reducers: {
        setUsers: (state, action) => {
            state.users = action.payload;
            state.usersObjects = action.payload?.reduce((acc, curr) => {
               acc[curr.id] = curr;
               return acc;
            }, {})
        }
    }
})



export const { setUsers } = usersSlice.actions;

export default usersSlice.reducer;
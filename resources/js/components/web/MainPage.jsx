import React from 'react';
import ReactDOM from 'react-dom';

import Navbar from './partials/Navbar';
import SearchBox from './partials/SearchBox';

const MainPage = () => {
    return (
        <div className='MainPage'>
            <Navbar/>
            <SearchBox/>
        </div>
    );
}

export default MainPage;
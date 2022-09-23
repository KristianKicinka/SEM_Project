import React from 'react';
import ReactDOM from 'react-dom';

import Navbar from './Navbar';
import SearchBox from './SearchBox';

const MainPage = () => {
    return (
        <div className='MainPage'>
            <Navbar/>
            <SearchBox/>
        </div>
    );
}

export default MainPage;
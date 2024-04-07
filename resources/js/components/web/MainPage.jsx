import React, { useState } from 'react';
import ReactDOM from 'react-dom';

import Navbar from './partials/Navbar';
import SearchBox from './partials/SearchBox';

import Results from './partials/Results';
import LoadingModal from './partials/LoadingModal';

import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

const MainPage = () => {

    const [hashTypes, setHashTypes] = useState([]);

    return (
        <div className='MainPage'>
            <Navbar />

            <SearchBox hashTypes={hashTypes} setHashTypes={setHashTypes} />

            <ToastContainer
                position="bottom-right"
                autoClose={5000}
                hideProgressBar={false}
                newestOnTop={false}
                closeOnClick
                rtl={false}
                pauseOnFocusLoss
                draggable
                pauseOnHover
                theme="colored"
            />
        </div>
    )
}

export default MainPage;
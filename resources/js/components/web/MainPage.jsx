import React, { useState } from 'react';
import ReactDOM from 'react-dom';

import Navbar from './partials/Navbar';
import SearchBox from './partials/SearchBox';

import Results from './partials/Results';
import LoadingModal from './partials/LoadingModal';

import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

const MainPage = () => {

    const [results, setResults] = useState([]);
    const [hashTypes, setHashTypes] = useState([]);

    const [showResults, setShowResults] = useState(false);
    const [showLoading, setShowLoading] = useState(false);
    const [loadingData, setLoadingData] = useState(
        {progress:0, message:'Hash process was created'}
    );

    const handleCloseResults = () => setShowResults(false);
    const handleShowResults = () => setShowResults(true);

    const handleCloseLoading = () => setShowLoading(false);
    const handleShowLoading = () => setShowLoading(true);

    return (
        <div className='MainPage'>
            <Navbar />

            <SearchBox
                handleShowLoading={handleShowLoading} 
                handleCloseLoading={handleCloseLoading}
                handleShowResults={handleShowResults}
                setResults={setResults}
                hashTypes={hashTypes}
                setHashTypes={setHashTypes}
                setLoadingData={setLoadingData}
            />

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
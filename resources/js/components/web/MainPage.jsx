import React, { useState } from 'react';
import ReactDOM from 'react-dom';

import Navbar from './partials/Navbar';
import SearchBox from './partials/SearchBox';

import Results from './partials/Results';
import LoadingModal from './partials/LoadingModal';

import HashTypeAlert from './partials/HashTypeAlert';

const MainPage = () => {

    const [results, setResults] = useState([]);
    const [hashTypes, setHashTypes] = useState([]);

    const [showResults, setShowResults] = useState(false);
    const [showLoading, setShowLoading] = useState(false);
    const [loadingData, setLoadingData] = useState({progress:0, message:'Hash process was created'});

    const [showAlert, setShowAlert] = useState(false);

    const handleCloseResults = () => setShowResults(false);
    const handleShowResults = () => setShowResults(true);

    const handleCloseLoading = () => setShowLoading(false);
    const handleShowLoading = () => setShowLoading(true);

    const handleCloseAlert = () => setShowAlert(false);
    const handleShowAlert = () => setShowAlert(true);

    return (
        <div className='MainPage'>
            <Navbar/>

            <SearchBox  
                handleShowLoading={handleShowLoading} 
                handleCloseLoading={handleCloseLoading}
                handleShowResults={handleShowResults}
                setResults={setResults}
                hashTypes={hashTypes}
                setHashTypes={setHashTypes}
                handleShowAlert={handleShowAlert}
                setLoadingData={setLoadingData}
            />

            <LoadingModal show={showLoading} handleClose={handleCloseLoading} loadingData={loadingData} />
            <HashTypeAlert showAlert={showAlert} setShowAlert={setShowAlert} />
            <Results show={showResults} handleClose={handleCloseResults} results={results} hashTypes={hashTypes} />
        </div>
    )
}

export default MainPage;
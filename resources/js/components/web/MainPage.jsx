import React, { useState } from 'react';
import ReactDOM from 'react-dom';

import Navbar from './partials/Navbar';
import SearchBox from './partials/SearchBox';

import Results from './partials/Results';
import LoadingModal from './partials/LoadingModal';

const MainPage = () => {

    const [results, setResults] = useState([]);
    const [hashTypes, setHashTypes] = useState([]);

    const [showResults, setShowResults] = useState(false);
    const [showLoading, setShowLoading] = useState(false);

    const handleCloseResults = () => setShowResults(false);
    const handleShowResults = () => setShowResults(true);

    const handleCloseLoading = () => setShowLoading(false);
    const handleShowLoading = () => setShowLoading(true);

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
            />
            <div className="btn btn-danger" onClick={handleShowLoading}>Show modal</div>
            <LoadingModal show={showLoading} handleClose={handleCloseLoading} />
            <Results show={showResults} handleClose={handleCloseResults} results={results} hashTypes={hashTypes} />
        </div>
    );
}

export default MainPage;
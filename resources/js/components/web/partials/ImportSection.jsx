import React from 'react';
import ReactDOM from 'react-dom';
import ApkInput from './ApkInput';
import AppNamesInput from './AppNamesInput';

const ImportSection = ({
    handleShowLoading, handleCloseLoading, handleShowResults,
    handleShowAlert, setResults, hashTypes, setLoadingData
}) => {
    return (
        <div className="row g-5 px-4">
            <div className='col-sm-12 col-md-6'>
                <ApkInput 
                    handleShowLoading={handleShowLoading} 
                    handleCloseLoading={handleCloseLoading}
                    handleShowResults={handleShowResults}
                    handleShowAlert={handleShowAlert}
                    setResults={setResults}
                    hashTypes={hashTypes}
                    setLoadingData={setLoadingData}
                 />
            </div>
            <div className='col-sm-12 col-md-6'>
                <AppNamesInput/>
            </div>
        </div>
    );
}

export default ImportSection;
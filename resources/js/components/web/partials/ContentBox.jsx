import React from 'react';
import ReactDOM from 'react-dom';
import AppItem from './AppItem';


const ContentBox = (
    {items, handleShowLoading, handleCloseLoading, handleShowResults,
        setResults, hashTypes, handleShowAlert, setLoadingData}) => {
    return (
        <div className='ContentBox pt-4'>
            <div className="container pb-4">
                <div className="row g-2">
                    {items.map((item, index) => {
                        return (
                            <div key={index} id='appItem' className="col-sm-3">
                                <AppItem 
                                    item={item}
                                    handleCloseLoading={handleCloseLoading}
                                    handleShowLoading={handleShowLoading}
                                    handleShowResults={handleShowResults}
                                    handleShowAlert={handleShowAlert}
                                    setResults={setResults}
                                    hashTypes={hashTypes}
                                    setLoadingData={setLoadingData}
                                  />
                            </div>
                        )
                    })}
                </div>
            </div>
        </div>
    );
}

export default ContentBox;
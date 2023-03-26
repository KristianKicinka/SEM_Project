import React from 'react';
import ReactDOM from 'react-dom';
import AppItem from './AppItem';

const ContentBox = ({items, handleShowLoading, handleCloseLoading, handleShowResults, setResults, hashTypes}) => {
    return (
        <div className='ContentBox pt-4'>
            <div className="container">
                <div className="row g-2">
                    {items.map((item, index) => {
                        return (
                            <div key={index} id='appItem' className="col-sm-3">
                                <AppItem 
                                    item={item}
                                    handleCloseLoading={handleCloseLoading}
                                    handleShowLoading={handleShowLoading}
                                    handleShowResults={handleShowResults}
                                    setResults={setResults}
                                    hashTypes={hashTypes}
                                  />
                            </div>
                        )
                    })};
                </div>
            </div>
        </div>
    );
}

export default ContentBox;
import React from 'react';
import ReactDOM from 'react-dom';
import AppItem from './AppItem';

const ContentBox = ({items}) => {
    return (
        <div className='ContentBox pt-4'>
            <div className="container">
                <div className="row g-2">
                    {items.map((item, index) => {
                        return (
                            <div key={index} id='appItem' className="col-sm-3">
                                <AppItem item={item} />
                            </div>
                        )
                    })};
                </div>
            </div>
        </div>
    );
}

export default ContentBox;
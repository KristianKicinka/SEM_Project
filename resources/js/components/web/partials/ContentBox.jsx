/**
 * @file ContentBox.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React from 'react';
import ReactDOM from 'react-dom';
import AppItem from './AppItem';


const ContentBox = ({ items, hashTypes }) => {

    // Component body
    return (
        <div className='ContentBox pt-4'>
            <div className="container pb-4">
                <div className="row g-2">
                    {items.map((item, index) => {
                        return (
                            <div key={index} id='appItem' className="col-sm-3">
                                <AppItem item={item} hashTypes={hashTypes} />
                            </div>
                        )
                    })}
                </div>
            </div>
        </div>
    );
}

export default ContentBox;
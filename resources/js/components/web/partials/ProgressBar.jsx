/**
 * @file ProgressBar.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { useState } from 'react';
import ReactDOM from 'react-dom';
import { Modal, Button, Spinner } from 'react-bootstrap';


// Progress bar component body
const ProgressBar = ({ description, completed }) => {

    // Component body
    return (
        <div className='ProgressBar'>
            <div className='container'>
                <div className='row'>
                    <div className='progress'>
                        <div
                            className='progress-bar w-25'
                            role='progressbar'
                            aria-valuenow='25'
                            aria-valuemin='0'
                            aria-valuemax='100'
                        >
                            {completed}%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ProgressBar;

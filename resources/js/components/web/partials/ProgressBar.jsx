import React, { useState } from 'react';
import ReactDOM from 'react-dom';
import { Modal, Button, Spinner } from 'react-bootstrap';

const ProgressBar = ({ description, completed }) => {
    return (
        <div className='ProgressBar'>
            <div className='container'>
                <div className='row'>{description}</div>
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

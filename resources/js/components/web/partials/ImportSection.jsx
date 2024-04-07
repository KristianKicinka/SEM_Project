/**
 * @file ImportSection.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React from 'react';
import ReactDOM from 'react-dom';

import ApkInput from './ApkInput';
import AppNamesInput from './AppNamesInput';


const ImportSection = ({ hashTypes }) => {

    // Component body
    return (
        <div className="row g-5 px-4">
            <div className='col-sm-12 col-md-6'>
                <ApkInput hashTypes={hashTypes} />
            </div>
            <div className='col-sm-12 col-md-6'>
                <AppNamesInput hashTypes={hashTypes} />
            </div>
        </div>
    );
}

export default ImportSection;
/**
 * @file HashTypePicker.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { useState } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';


const HashTypePicker = ({hashTypes, setHashTypes}) => {

    /**
     * @brief The function ensures handle checkbox changes
     * @param {*} e OnChange event
     */
    const checkboxChange = (e) =>{

        let newHashesTypes = [...hashTypes, e.target.id];

        if (hashTypes.includes(e.target.id))
            newHashesTypes = newHashesTypes.filter(hashType => hashType !== e.target.id);
   
        setHashTypes(newHashesTypes);
    }

    // Component body
    return (
        <div className='HashTypePicker container py-4'>
           <div className="row">
                <div className="col"></div>
                <div className="col-auto bg-light text-dark p-3 rounded-3">
                    <b className='px-3'>Select hash types : </b>
                    <Form.Check onChange={checkboxChange} inline label="JA3" name="JA3_checkbox" type='checkbox' id='JA3' />
                    <Form.Check onChange={checkboxChange} inline label="JA3S" name="JA3S_checkbox" type='checkbox' id='JA3S' />
                    <Form.Check onChange={checkboxChange} inline label="JA4" name="JA4_checkbox" type='checkbox' id='JA4' />
                    <Form.Check onChange={checkboxChange} inline label="JA4S" name="JA4S_checkbox" type='checkbox' id='JA4S' />
                    <Form.Check onChange={checkboxChange} inline label="JA4X" name="JA4X_checkbox" type='checkbox' id='JA4X' />
                </div>
                <div className="col"></div>
           </div>
        </div>
    );
}

export default HashTypePicker;
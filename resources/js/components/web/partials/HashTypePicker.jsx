/**
 * @file HashTypePicker.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import AuthUser from '../../../AuthUser';
import axios from 'axios';

const HashTypePicker = ({hashTypes, setHashTypes}) => {
    const [customHashTypes, setCustomHashTypes] = useState([]);
    const [loading, setLoading] = useState(false);
    const { http, token } = AuthUser();

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

    /**
     * @brief Load custom hash types for authenticated users
     */
    const loadCustomHashTypes = async () => {
        if (!token) return;
        
        try {
            setLoading(true);
            const response = await http.get('/custom-hash-types');
            setCustomHashTypes(response.data.data || response.data);
        } catch (error) {
            console.error('Error loading custom hash types:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadCustomHashTypes();
    }, [token]);

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
                    
                    {/* Show custom hash types only for authenticated users */}
                    {token && (
                        <>
                            <hr className="my-2" />
                            <small className="text-muted">Your Custom Hash Types:</small>
                            <br className="my-2" />
                            {loading ? (
                                <div className="text-center">
                                    <small className="text-muted">Loading...</small>
                                </div>
                            ) : customHashTypes.filter(hashType => hashType.is_active !== false).length > 0 ? (
                                customHashTypes.filter(hashType => hashType.is_active !== false).map((hashType) => (
                                    <Form.Check 
                                        key={hashType.id}
                                        onChange={checkboxChange} 
                                        inline 
                                        label={hashType.display_name} 
                                        name={`${hashType.name}_checkbox`} 
                                        type='checkbox' 
                                        id={hashType.name} 
                                    />
                                ))
                            ) : (
                                <div className="text-center">
                                    <small className="text-muted">No custom hash types created yet</small>
                                </div>
                            )}
                        </>
                    )}
                </div>
                <div className="col"></div>
           </div>
        </div>
    );
}

export default HashTypePicker;
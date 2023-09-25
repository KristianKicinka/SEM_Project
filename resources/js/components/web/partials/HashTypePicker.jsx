import React, { useState } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';


const HashTypePicker = ({hashTypes, setHashTypes}) => {

    const checkboxChange = (e) =>{

        let newHashesTypes = [...hashTypes, e.target.id];

        if (hashTypes.includes(e.target.id))
            newHashesTypes = newHashesTypes.filter(hashType => hashType !== e.target.id);
   
        setHashTypes(newHashesTypes);
    }

    console.log(hashTypes);

    return (
        <div className='HashTypePicker container py-4'>
           <div className="row">
                <div className="col"></div>
                <div className="col bg-light text-dark p-3 rounded-3">
                    <b className='px-3'>Select hash types : </b>
                    <Form.Check onChange={checkboxChange} inline label="JA3" name="JA3_checkbox" type='checkbox' id='JA3' />
                    <Form.Check onChange={checkboxChange} inline label="JA3S" name="JA3S_checkbox" type='checkbox' id='JA3S' />
                    <Form.Check disabled onChange={checkboxChange} inline label="Flowmon" name="NetFlow_checkbox" type='checkbox' id='NetFlow' />
                </div>
                <div className="col"></div>
           </div>
        </div>
    );
}

export default HashTypePicker;
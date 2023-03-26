import React, { useState } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';

const HashTypePicker = ({}) => {
    return (
        <div className='HashTypePicker container py-4'>
           <div className="row">
                <div className="col"></div>
                <div className="col bg-light text-dark p-3 rounded-3">
                    <b className='px-3'>Select hash types : </b>
                    <Form.Check inline label="JA3" name="group1" type='checkbox' id='ja3_checkbox' />
                    <Form.Check inline label="JA3S" name="group1" type='checkbox' id='ja3S_checkbox' />
                    <Form.Check inline label="NetFlow" name="group1" type='checkbox' id='netflow_checkbox' />
                </div>
                <div className="col"></div>
           </div>
        </div>
    );
}

export default HashTypePicker;
import React from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import Button from 'react-bootstrap/Button';

const saveFiles = (event) => {
    event.preventDefault();
    console.log('saving files');
}

const ApkInput = () => {
    return (
        <div className='bg-light text-dark p-3 rounded-3'>
            <Form noValidate onSubmit={saveFiles} className='container'>
                <h3 className='pb-2'>Insert App name list</h3>
                <Form.Group controlId="formFileNames" className="row">
                    <Form.Control type="file" className='col' />
                    <Button id="submit_file_names_input" type='submit' className='btn-search text-light col-2 mx-2'><i className='fa-solid fa-file-import'></i></Button>
                </Form.Group>
            </Form>
        </div>
    );
}

export default ApkInput;
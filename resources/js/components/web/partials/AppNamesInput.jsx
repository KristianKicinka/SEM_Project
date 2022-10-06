import React from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import Button from 'react-bootstrap/Button';

const saveFiles = () => {
    console.log('saving files');
}

const ApkInput = () => {
    return (
        <Form noValidate onSubmit={saveFiles} className='p-3 text-dark'>
            <h3 className='pb-2'>Insert App name list</h3>
            <Form.Group controlId="formFileNames" className="row">
                <Form.Control type="file" className='col' />
                <Button id="submit_file_names_input" type='submit' className='btn-search text-light col-1 mx-2'><i class="fa-solid fa-file-import"></i></Button>
            </Form.Group>
        </Form>
    );
}

export default ApkInput;
import React, { useState } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import Button from 'react-bootstrap/Button';

const ApkInput = () => {

    const [file, setFile] = useState();

    const saveFiles = (event) => {
        event.preventDefault();
        axios.post('/saveNamesListFile',{file: file},{
            headers: {
              'Content-Type': 'multipart/form-data'
            }
        }).then(res=>{
            console.log(res.data);
        });
    }

    return (
        <div className='bg-light text-dark p-3 rounded-3'>
            <Form onSubmit={saveFiles} className='container'>
                <h3 className='pb-2'>Insert App name list</h3>
                <Form.Group controlId="formFileNames" className="row">
                    <Form.Control type="file" className='col'
                        onChange={(e) => setFile(e.target.files)} accept='.txt' required />
                    <Button id="submit_file_names_input" type='submit' className='btn-search text-light col-2 mx-2'><i className='fa-solid fa-file-import'></i></Button>
                </Form.Group>
            </Form>
        </div>
    );
}

export default ApkInput;
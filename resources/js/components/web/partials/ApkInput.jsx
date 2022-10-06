import React, { useState } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import Button from 'react-bootstrap/Button';
import http from '../../../http';
import axios from 'axios';



const ApkInput = () => {

    const [files, setFiles] = useState([]);

    const saveFiles = (event) => {
        event.preventDefault();
        axios.post('/saveApkFile',{files: files},{
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
                <h3 className='pb-2'>Insert APK files</h3>
                <Form.Group controlId="formFileAPK" className="row">
                    <Form.Control type="file" multiple className='col'
                        onChange={(e) => setFiles(e.target.files)} accept='.txt' required />
                    <Button id="submit_apk_files" type='submit' className='btn-search text-light col-2 mx-2'><i className='fa-solid fa-file-import'></i></Button>
                </Form.Group>
            </Form>
        </div>
    );
}

export default ApkInput;
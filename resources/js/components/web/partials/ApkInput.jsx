import React, { useState } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import Button from 'react-bootstrap/Button';
import http from '../../../http';
import axios from 'axios';





const ApkInput = ({handleShowLoading, handleCloseLoading, handleShowResults, setResults}) => {

    const [files, setFiles] = useState([]);

    const saveApkFiles = (event) => {
        event.preventDefault();

        const formData = new FormData();
        files.forEach((file) => {
            formData.append('files[]',file);
        });

        console.log(formData.getAll('files[]'));
    
        axios.post('/saveApkFile',formData ).then( res => {
            if(res.data !== 'Upload Error!')
                createHash(res.data[0]);
                handleShowLoading();
        });
    }

    const createHash = (fileName) => {
        console.log(fileName);

        let data = {
            'file_name': fileName,
            'apk_type': 'inserted',
            'hash_type': 'ja3'
        }

        axios.post('/createHash', data).then( res => {
            console.log(res.data);
            setResults(res.data);
            handleCloseLoading();
            handleShowResults();
        });
    }


    return (
        <div className='bg-light text-dark p-3 rounded-3'>
            <Form onSubmit={saveApkFiles} className='container' encType="multipart/form-data">
                <h3 className='pb-2'>Insert APK files</h3>
                <Form.Group controlId="formFileAPK" className="row">
                    <Form.Control type="file" multiple className='col'
                        onChange={e =>{setFiles(Array.from(e.target.files))}} accept='.apk' required />
                    <Button id="submit_apk_files" type='submit' onClick={saveApkFiles} className='btn-search text-light col-2 mx-2'><i className='fa-solid fa-file-import'></i></Button>
                </Form.Group>
            </Form>
            
        </div>
    );
}

export default ApkInput;
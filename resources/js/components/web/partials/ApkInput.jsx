import React, { useState } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import Button from 'react-bootstrap/Button';
import http from '../../../http';
import axios from 'axios';

import Results from './Results';
import LoadingModal from './LoadingModal';



const ApkInput = () => {

    const [files, setFiles] = useState([]);
    const [results, setResults] = useState([]);

    const [showResults, setShowResults] = useState(false);
    const [showLoading, setShowLoading] = useState(false);

    const handleCloseResults = () => setShowResults(false);
    const handleShowResults = () => setShowResults(true);

    const handleCloseLoading = () => setShowLoading(false);
    const handleShowLoading = () => setShowLoading(true);

    const saveApkFiles = (event) => {
        event.preventDefault();

        const formData = new FormData();
        files.forEach((file) => {
            formData.append('files[]',file);
        });

        console.log(formData.getAll('files[]'));

        let results = {
            apk_name : 'My app',
            package_name : 'Package',
            version_name : '1.0',
            hashes: [
                '66918128f1b9b03303d77c6f2eefd128',
                '66918128f1b9b03303d77c6f2eefd128',
                '66918128f1b9b03303d77c6f2eefd128',
            ]
        }

        //setResults(results);

        //handleShowResults();
    
        
        axios.post('/saveApkFile',formData ).then( res => {
            if(res.data !== 'Upload Error!')
                createHash(res.data[0]);
                handleShowLoading();
        });
    }

    const createHash = (fileName) => {
        console.log(fileName);
        axios.post('/createHashFromApkFile', {'file_name':fileName}).then( res => {
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
            <LoadingModal show={showLoading} handleClose={handleCloseLoading} />
            <Results show={showResults} handleClose={handleCloseResults} results={results} />
        </div>
    );
}

export default ApkInput;
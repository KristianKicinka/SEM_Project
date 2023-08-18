import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import Button from 'react-bootstrap/Button';
import axios from 'axios';
import { setNewActiveProcess } from '../../../processManagement';




const ApkInput = ({
    handleShowLoading, handleCloseLoading, handleShowResults,
    handleShowAlert, setResults, hashTypes, setLoadingData
}) => {

    const [files, setFiles] = useState([]);
    const [pollingInterval, setPollingInterval] = useState(null);
    let process_id = null; 

    const saveApkFiles = async (event) => {
        event.preventDefault();

        if(hashTypes.length === 0){
            console.log("Select hash type");
            handleShowAlert();
            return;
        }

        const formData = new FormData();
        files.forEach((file) => {
            formData.append('files[]',file);
        });

        console.log(formData.getAll('files[]'));
        try {
            let response = await axios.post('/api/save-apk-file', formData);
            createHash(response.data[0]);
        } catch (error) {
            console.log(`ERROR: ${error}`);
        }
    }

    const createHash = async (fileName) => {
        console.log(fileName);
        handleShowLoading();

        process_id = setNewActiveProcess();

        console.log(`after set process id ${process_id}`);
    
        let data = {
            'file_name': fileName,
            'input_type': 'apk_file',
            'hash_types': hashTypes,
            'frontend_id': process_id,
        }

        axios.post('/api/create-hash', data).then( res => {
            console.log(res.data);
            pollStatus();
            setPollingInterval(setInterval(pollStatus, 2000));
        });
    }

    const handleResults = async () => {
        clearInterval(pollingInterval);
        setPollingInterval(null);

        try {
            let results = await axios.post('/api/get-process-results', {'frontend_id': process_id});
            console.log(results.data);
            setResults(results.data);
            handleCloseLoading();
            handleShowResults();
        } catch (error) {
            console.log(`ERROR: ${error}`);
        }
    }

    const pollStatus = async () => {

        let info = await getProcessInfo(process_id);
        console.log(info.status);
        setLoadingData(info);

        if(info.status === 'finished')
            handleResults();
    }

    const getProcessInfo = async (processID) => {
        try {
            let results = await axios.post('/api/get-process-info', {'frontend_id': processID});
            console.log(results.data);
            return results.data;
        } catch (error) {
            console.log(`ERROR: ${error}`);
        }
    }

    useEffect(() => {
        return () => clearInterval(pollingInterval);
      }, [pollingInterval]);

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
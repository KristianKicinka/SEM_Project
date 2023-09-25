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

    const [apkFile, setApkFile] = useState(null);
    const [pollingInterval, setPollingInterval] = useState(null);
    let process_id = null;

    const createHash = async (event) => {
        event.preventDefault();

        if(hashTypes.length === 0){
            console.log("Select hash type");
            handleShowAlert();
            return;
        }

        handleShowLoading();

        process_id = setNewActiveProcess();

        console.log(`after set process id ${process_id}`);

        const data = new FormData();
        data.append("apk_file", apkFile);
        data.append("hash_types", JSON.stringify(hashTypes));
        data.append("frontend_id", process_id);

        try {
            let results = await axios.post('/api/create-hash-apk', data);
            console.log(results.data);
            pollStatus();
            setPollingInterval(setInterval(pollStatus, 2000));
        } catch (error) {
            clearInterval(pollingInterval);
            setPollingInterval(null);
            handleCloseLoading();
            console.log(error);
        }
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
            <Form onSubmit={createHash} className='container' encType="multipart/form-data">
                <h3 className='pb-2'>Insert APK file</h3>
                <Form.Group controlId="formFileAPK" className="row">
                    <Form.Control type="file" className='col'
                        onChange={e=>{setApkFile(e.target.files[0])}} accept='.apk' required />
                    <Button id="submit_apk_files" type='submit' className='btn-search text-light col-2 mx-2'><i className='fa-solid fa-file-import'></i></Button>
                </Form.Group>
            </Form>
        </div>
    );
}

export default ApkInput;
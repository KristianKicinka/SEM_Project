import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import Button from 'react-bootstrap/Button';
import axios from 'axios';

import { setNewActiveProcess } from '../../../processManagement';
import { toast } from 'react-toastify';


const ApkInput = ({
    handleShowLoading, handleCloseLoading, handleShowResults,
    setResults, hashTypes, setLoadingData
}) => {

    const [apkFile, setApkFile] = useState(null);
    const [pollingInterval, setPollingInterval] = useState(null);
    let process_id = null;

    const createHash = async (event) => {
        event.preventDefault();

        if(hashTypes.length === 0){
            toast.error('Hash type must be selected!');
            return;
        }

        handleShowLoading();

        process_id = setNewActiveProcess();

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
            toast.error('Hash generation error!');
            console.log(`ERROR: ${error}`);
        }
    }

    const handleResults = async () => {
        
        clearInterval(pollingInterval);
        setPollingInterval(null);

        let identifiers = [process_id];
        console.log(identifiers);

        try {

            const data = new FormData();
            data.append("identifiers", JSON.stringify(identifiers));

            let results = await axios.post('/api/get-process-results', data);
            console.log(results.data);

            setResults(results.data);

            handleCloseLoading();
            handleShowResults();
        } catch (error) {
            handleCloseLoading();
            toast.error('Hash generation error!');
            console.log(`ERROR: ${error}`);
        }
    }

    const pollStatus = async () => {
        let info = await getProcessInfo(process_id);

        setLoadingData(info);
        let process = info[process_id];

        if(process.status === 'finished')
            handleResults();

        if(process.status === 'failed'){
            handleCloseLoading();

            clearInterval(pollingInterval);
            setPollingInterval(null);

            toast.error('Hash generation error!');
            console.log(`ERROR: ${error}`);
        }
    }

    const getProcessInfo = async (processID) => {
        try {
            const data = new FormData();
            data.append("identifiers", JSON.stringify([processID]));

            let results = await axios.post('/api/get-process-info', data);

            return results.data;
        } catch (error) {
            toast.error('Hash generation error!');
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
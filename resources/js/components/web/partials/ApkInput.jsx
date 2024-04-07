import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import Button from 'react-bootstrap/Button';
import axios from 'axios';

import { setNewActiveChannel, setNewActiveProcess } from '../../../processManagement';
import { toast } from 'react-toastify';
import LoadingModal from './LoadingModal';


const ApkInput = ({ hashTypes }) => {

    const [apkFiles, setApkFiles] = useState([]);
    const [showLoading, setShowLoading] = useState(false);
    const [channelID, setChannelID] = useState(false);
    const [processes, setProcesses] = useState([]);

    const createHash = async (event) => {
        event.preventDefault();

        if(hashTypes.length === 0){
            toast.error('Hash type must be selected!');
            return;
        }

        let channel_id = setNewActiveChannel();
        let processes = [];

        setChannelID(channel_id);

        const data = new FormData();

        apkFiles.map((apkFile) => {
            processes[apkFile.name] = setNewActiveProcess();
            data.append("files[]", apkFile);
        });
        
        data.append("hash_types", JSON.stringify(hashTypes));
        data.append("channel_id", channel_id);

        console.log(data);

        try {
            let results = await axios.post('/api/create-hash-apk', data, { 
                headers: {
                    'Content-Type': 'multipart/form-data',
                }
            });
            console.log(results.data);

            setProcesses(results.data.processes);
            setShowLoading(true);

        } catch (error) {
            setShowLoading(false);
            toast.error('Hash generation error!');
            console.log(`ERROR: ${error}`);
        }
    }

    const closeLoading = () => {
        setShowLoading(false);
    }

    useEffect(() => {

    }, []);

    return (
        <div className='bg-light text-dark p-3 rounded-3'>
            <Form onSubmit={createHash} className='container' encType="multipart/form-data">
                <h3 className='pb-2'>Insert APK file</h3>
                <Form.Group controlId="formFileAPK" className="row">
                    <Form.Control type="file" className='col'
                        onChange={e=>{setApkFiles(Array.from(e.target.files))}} accept='.apk' required multiple/>
                    <Button id="submit_apk_files" type='submit' className='btn-search text-light col-2 mx-2'><i className='fa-solid fa-file-import'></i></Button>
                </Form.Group>
            </Form>
            {showLoading && (
                <LoadingModal processes={processes} channel_id={channelID} onClose={closeLoading} hashTypes={hashTypes} />
            )}
        </div>
    );
}

export default ApkInput;
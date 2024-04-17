/**
 * @file ApkInput.jsx
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

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

    // Max file size supported
    const maxFileSize = 950 * 1024 * 1024;

    /**
     * @brief The function ensures calling API request for hash generation from APK file
     * @param {*} event OnClick event
     */
    const createHash = async (event) => {
        event.preventDefault();
        let curr_files_size = 0;

        // Validate hash types selection
        if(hashTypes.length === 0){
            toast.error('Hash type must be selected!');
            return;
        }

        // Check max apk files count
        if(apkFiles.length > 5){
            toast.error('Too many APK files inserted. Max files allowed : 5');
            return;
        }

        // Get selected apk's full size
        for (let i = 0; i < apkFiles.length; i++) {
            curr_files_size += apkFiles[i].size;
        }

        // Check max apk files size
        if(curr_files_size > maxFileSize){
            toast.error('List of APK files is too large. Max list size : 950 M');
            return;
        }

        let channel_id = setNewActiveChannel();
        let processes = [];

        setChannelID(channel_id);
        const data = new FormData();

        apkFiles.map((apkFile) => {
            processes.push({
                process_id: setNewActiveProcess(),
                channel_id: channel_id,
                name: apkFile.name,
                message: "Waiting in queue",
                progress: 0,
                status: "in_queue"
            });
            data.append("files[]", apkFile);
        });

        data.append("processes", JSON.stringify(processes));
        data.append("hash_types", JSON.stringify(hashTypes));
        data.append("channel_id", channel_id);

        setProcesses(processes);
        setShowLoading(true);

        try {
            let results = await axios.post('/api/create-hash-apk', data, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                }
            });

        } catch (error) {
            setShowLoading(false);
            toast.error('Hash generation error!');
            console.log(`ERROR: ${error}`);
        }
    }

    /**
     * @brief The function ensures close loading modal box
     */
    const closeLoading = () => {
        setShowLoading(false);
    }

    useEffect(() => {

    }, []);

    // Component body
    return (
        <div className='bg-light text-dark p-3 rounded-3'>
            <Form onSubmit={createHash} className='container' encType="multipart/form-data">
                <h3 className='pb-2'>Insert APK file</h3>
                <Form.Group controlId="formFileAPK" className="row">
                    <Form.Control type="file" className='col'
                        onChange={e=>{setApkFiles(Array.from(e.target.files))}} accept='.apk' required multiple/>
                    <Button id="submit_apk_files" type='submit' className='btn-search text-light col-2 mx-2'>
                        <i className='fa-solid fa-file-import'></i>
                    </Button>
                </Form.Group>
            </Form>
            {showLoading && (
                <LoadingModal processes={processes} channel_id={channelID} onClose={closeLoading} hashTypes={hashTypes} />
            )}
        </div>
    );
}

export default ApkInput;

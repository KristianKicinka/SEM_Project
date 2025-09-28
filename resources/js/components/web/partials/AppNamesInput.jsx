/**
 * @file AppNamesInput.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import Button from 'react-bootstrap/Button';

import { setNewActiveChannel, setNewActiveProcess } from '../../../processManagement';
import { toast } from 'react-toastify';
import LoadingModal from './LoadingModal';
import AuthUser from '../../../AuthUser';


const AppNamesInput = ({ hashTypes, customHashTypes = [] }) => {

    const [file, setFile] = useState(null);
    const [showLoading, setShowLoading] = useState(false);
    const [channelID, setChannelID] = useState(false);
    const [processes, setProcesses] = useState([]);
    const { http, token } = AuthUser();


    /**
     * @brief The function ensures handling file input changes
     * @param {*} file Text file
     */
    const handleChange = file => {
        setFile(file[0]);
    }

    /**
     * @brief The function ensures saving file with package names
     * @param {*} event OnClick event
     */
    const saveFilesNames = async (event) => {
        event.preventDefault();

        if(hashTypes.length === 0){
            toast.error('Hash type must be selected!');
            return;
        }

        let channel_id = setNewActiveChannel();
        setChannelID(channel_id);

        const data = new FormData();
        data.append("text_file", file);
        data.append("channel_id", channel_id);
        data.append("hash_types", JSON.stringify(hashTypes));

        // Add custom hash types if user is authenticated and has selected them
        if (token && customHashTypes.length > 0) {
            const selectedCustomTypes = customHashTypes.filter(customType => 
                hashTypes.includes(customType.name)
            );
            if (selectedCustomTypes.length > 0) {
                data.append("custom_hash_types", JSON.stringify(selectedCustomTypes.map(type => type.id)));
            }
        }

        try {
            let results = await axios.post('/api/create-hash-textfile', data );

            setProcesses(results.data.processes);
            setShowLoading(true);
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
            <Form onSubmit={saveFilesNames} className='container' encType="multipart/form-data" >
                <h3 className='pb-2'>Insert app package names list</h3>
                <Form.Group controlId="formFileNames" className="row">
                    <Form.Control type="file" className='col' accept='.txt'
                        onChange={(e) => handleChange(e.target.files)} required />
                    <Button 
                        id="submit_file_names_input"
                        type='submit' 
                        onClick={saveFilesNames} 
                        className='btn-search text-light col-2 mx-2'>
                            <i className='fa-solid fa-file-import'></i>
                    </Button>
                </Form.Group>
            </Form>
            {showLoading && (
                <LoadingModal processes={processes} channel_id={channelID} onClose={closeLoading} hashTypes={hashTypes} customHashTypes={customHashTypes} />
            )}
        </div>
    );
}

export default AppNamesInput;
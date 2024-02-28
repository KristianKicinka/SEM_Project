import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import Button from 'react-bootstrap/Button';

import { setNewActiveChannel, setNewActiveProcess } from '../../../processManagement';
import { toast } from 'react-toastify';
import LoadingModal from './LoadingModal';

const AppNamesInput = ({
    handleShowLoading, handleCloseLoading, handleShowResults,
    setResults, hashTypes, setLoadingData
}) => {

    const [file, setFile] = useState(null);
    const [showLoading, setShowLoading] = useState(false);
    const [channelID, setChannelID] = useState(false);
    const [processes, setProcesses] = useState([]);

    const handleChange = file => {
        setFile(file[0]);
    }

    const saveFilesNames = async (event) => {
        event.preventDefault();

        if(hashTypes.length === 0){
            toast.error('Hash type must be selected!');
            return;
        }

        let channel_id = setNewActiveChannel();
        console.log(channel_id);
        setChannelID(channel_id);

        const data = new FormData();
        data.append("text_file", file);
        data.append("channel_id", channel_id);
        data.append("hash_types", JSON.stringify(hashTypes));

        try {
            let results = await axios.post('/api/create-hash-textfile', data );
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
                <LoadingModal processes={processes} channel_id={channelID} onClose={closeLoading} hashTypes={hashTypes} />
            )}
        </div>
    );
}

export default AppNamesInput;
import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import Button from 'react-bootstrap/Button';
import { toast } from 'react-toastify';

const AppNamesInput = ({
    handleShowLoading, handleCloseLoading, handleShowResults,
    setResults, hashTypes, setLoadingData
}) => {

    const [file, setFile] = useState(null);
    const [pollingInterval, setPollingInterval] = useState(null);
    const [identifiers, setIdentifiers] = useState(null);

    const handleChange = file => {
        setFile(file[0]);
    }

    const saveFilesNames = (event) => {
        event.preventDefault();

        if(hashTypes.length === 0){
            toast.error('Hash type must be selected!');
            return;
        }

        const data = new FormData();
        data.append("text_file", file);
        data.append("hash_types", JSON.stringify(hashTypes));

        axios.post('/api/create-hash-textfile', data).then( res =>{
            console.log(res.data);
            setIdentifiers(res.data);
        });
    }

    const handleResults = async () => {
        clearInterval(pollingInterval);
        setPollingInterval(null);

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
        let info = await getProcessInfo(identifiers);

        Object.keys(identifiers).map((key) => {
            let id = identifiers[key];
            let process = info[id];

            if(process.status === 'finished')
                handleResults();

            if(process.status === 'failed'){
                handleCloseLoading();

                clearInterval(pollingInterval);
                setPollingInterval(null);

                toast.error('Hash generation error!');
                console.log(`ERROR: ${error}`);
            }
        });
        
        return;
        setLoadingData(info);
        let process = info[process_id];

        if(info.status === 'finished')
            handleResults();

        if(info.status === 'failed'){
            handleCloseLoading();
            clearInterval(pollingInterval);
            setPollingInterval(null);

            toast.error('Hash generation error!');
            console.log(`ERROR: ${error}`);
        }
    }

    const getProcessInfo = async (identifiers) => {
        try {
            const data = new FormData();
            data.append("identifiers", JSON.stringify([identifiers]));

            let results = await axios.post('/api/get-process-info', data);
            console.log(results.data);
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
        </div>
    );
}

export default AppNamesInput;
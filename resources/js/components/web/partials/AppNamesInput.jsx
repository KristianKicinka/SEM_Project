import React, { useState } from 'react';
import ReactDOM from 'react-dom';

import Form from 'react-bootstrap/Form';
import Button from 'react-bootstrap/Button';

const ApkInput = () => {

    const [file, setFile] = useState(null);

    const handleChange = file => {
        setFile(file[0]);
    }

    const saveFilesNames = (event) => {
        event.preventDefault();

        const formDataNames = new FormData();
        formDataNames.append("selectedFile", file);

        axios.post('/saveNamesListFile', formDataNames).then(res=>{
            console.log(res.data);
        });
    }

    return (
        <div className='bg-light text-dark p-3 rounded-3'>
            <Form onSubmit={saveFilesNames} className='container' encType="multipart/form-data" >
                <h3 className='pb-2'>Insert app package names list</h3>
                <Form.Group controlId="formFileNames" className="row">
                    <Form.Control type="file" className='col' accept='.txt'
                        onChange={(e) => handleChange(e.target.files)} required />
                    <Button id="submit_file_names_input" disabled type='submit' onClick={saveFilesNames} className='btn-search text-light col-2 mx-2'><i className='fa-solid fa-file-import'></i></Button>
                </Form.Group>
            </Form>
        </div>
    );
}

export default ApkInput;
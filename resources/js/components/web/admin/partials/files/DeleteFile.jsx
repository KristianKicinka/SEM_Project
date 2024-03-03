import React, { useState } from "react";
import ReactDOM from "react-dom";

import AuthUser from "../../../../../AuthUser";
import { Modal, Button } from 'react-bootstrap';

const DeleteFile = ({ show, file, handleClose, setFetchDataState }) => {

    const {http} = AuthUser();

    const handleDeleteFile = async () => {
        console.log(file);

        try {
            let resp = await http.post('/admin/file/delete', {file_id:file.file_id});
            console.log(resp);
            setFetchDataState(prevState => !prevState);
            handleClose();
        } catch (error) {
            console.log(error);
        }
    }

    return (
        <Modal show={show} onHide={handleClose}>
            <Modal.Header closeButton>
                <Modal.Title>Confirm Delete</Modal.Title>
            </Modal.Header>
            <Modal.Body>Are you sure you want to delete this file?</Modal.Body>
            <Modal.Footer>
                <Button variant="secondary" onClick={handleClose}>Cancel</Button>
                <Button variant="danger" onClick={handleDeleteFile}>Delete</Button>
            </Modal.Footer>
        </Modal>
    );
};

export default DeleteFile;

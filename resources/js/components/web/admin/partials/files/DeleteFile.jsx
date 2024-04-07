/**
 * @file DeleteFile.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { useState } from "react";
import ReactDOM from "react-dom";

import AuthUser from "../../../../../AuthUser";
import { Modal, Button } from 'react-bootstrap';


const DeleteFile = ({ show, file, handleClose, setFetchDataState }) => {

    const {http} = AuthUser();

    /**
     *  @brief The function ensures calling delete function from backend API
     */
    const handleDeleteFile = async () => {
        try {
            let resp = await http.post('/admin/file/delete', {file_id:file.file_id});
            setFetchDataState(prevState => !prevState);
            handleClose();
        } catch (error) {
            console.log(error);
        }
    }

    // Component body
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

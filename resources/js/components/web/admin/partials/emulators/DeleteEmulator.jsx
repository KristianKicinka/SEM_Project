/**
 * @file DeleteEmulator.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { useState } from "react";
import ReactDOM from "react-dom";

import AuthUser from "../../../../../AuthUser";
import { Modal, Button } from 'react-bootstrap';


const DeleteEmulator = ({ show, emulator, handleClose, setFetchDataState }) => {

    const {http} = AuthUser();

    /**
     * @brief The function ensures handling delete button on click event
     */
    const handleDeleteEmulator = async () => {

        try {
            let resp = await http.post('/admin/emulator/delete', { container_name:emulator.name,  network_name: emulator.network_interface });
            console.log(resp);
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
            <Modal.Body>Are you sure you want to delete this emulator?</Modal.Body>
            <Modal.Footer>
                <Button variant="secondary" onClick={handleClose}>Cancel</Button>
                <Button variant="danger" onClick={handleDeleteEmulator}>Delete</Button>
            </Modal.Footer>
        </Modal>
    );
};

export default DeleteEmulator;
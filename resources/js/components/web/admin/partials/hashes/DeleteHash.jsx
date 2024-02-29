import React, { useState } from "react";
import ReactDOM from "react-dom";

import AuthUser from "../../../../../AuthUser";
import { Modal, Button } from 'react-bootstrap';

const DeleteHash = ({ show, hash, handleClose, setFetchDataState }) => {

    const {http} = AuthUser();

    const handleDeleteHash = async () => {
        console.log(hash);

        try {
            let resp = await http.post('/admin/hash/delete', {hash_id:hash.id});
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
            <Modal.Body>Are you sure you want to delete this hash?</Modal.Body>
            <Modal.Footer>
                <Button variant="secondary" onClick={handleClose}>Cancel</Button>
                <Button variant="danger" onClick={handleDeleteHash}>Delete</Button>
            </Modal.Footer>
        </Modal>
    );
};

export default DeleteHash;

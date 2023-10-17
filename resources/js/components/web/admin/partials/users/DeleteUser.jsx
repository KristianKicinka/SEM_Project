import React, { useState } from "react";
import ReactDOM from "react-dom";

import AuthUser from "../../../../../AuthUser";
import { Modal, Button } from 'react-bootstrap';

const DeleteUser = ({ show, user, handleClose, setFetchDataState }) => {

    const {http} = AuthUser();

    const handleDeleteUser = async () => {
        console.log(user);

        try {
            let resp = await http.post('/admin/user/delete', {user_id:user.id});
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
            <Modal.Body>Are you sure you want to delete this user?</Modal.Body>
            <Modal.Footer>
                <Button variant="secondary" onClick={handleClose}>Cancel</Button>
                <Button variant="danger" onClick={handleDeleteUser}>Delete</Button>
            </Modal.Footer>
        </Modal>
    );
};

export default DeleteUser;

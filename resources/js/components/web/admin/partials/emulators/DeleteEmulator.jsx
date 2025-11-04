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
    const [isDeleting, setIsDeleting] = useState(false);

    /**
     * @brief The function ensures handling delete button on click event
     */
    const handleDeleteEmulator = async () => {
        if (!emulator) {
            console.error('No emulator selected for deletion');
            return;
        }

        setIsDeleting(true);

        try {
            let resp = await http.post('/admin/emulator/delete', { 
                container_name: emulator.name,  
                network_name: emulator.network_interface 
            });
            
            console.log('Delete response:', resp);
            
            // Check if response indicates success
            if (resp.data && resp.data.status === 'success') {
                // Refresh the emulators list
                setFetchDataState(prevState => !prevState);
                // Close the modal
                handleClose();
            } else {
                // Handle unexpected response format
                console.error('Unexpected response format:', resp);
                alert('Failed to delete emulator. Please try again.');
                setIsDeleting(false);
            }
        } catch (error) {
            console.error('Error deleting emulator:', error);
            
            // Show error message to user
            const errorMessage = error.response?.data?.message 
                || error.response?.data?.errors 
                || error.message 
                || 'Failed to delete emulator. Please try again.';
            
            alert(errorMessage);
            setIsDeleting(false);
            
            // Still close modal on error (user can see the result in the list)
            // Or keep it open if you want user to retry
            handleClose();
        }
    }

    // Component body
    return (
        <Modal show={show} onHide={handleClose}>
            <Modal.Header closeButton>
                <Modal.Title>Confirm Delete</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                Are you sure you want to delete this emulator?
                {emulator && (
                    <div className="mt-2">
                        <strong>Emulator:</strong> {emulator.name}
                    </div>
                )}
            </Modal.Body>
            <Modal.Footer>
                <Button variant="secondary" onClick={handleClose} disabled={isDeleting}>
                    Cancel
                </Button>
                <Button 
                    variant="danger" 
                    onClick={handleDeleteEmulator} 
                    disabled={isDeleting}
                >
                    {isDeleting ? 'Deleting...' : 'Delete'}
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

export default DeleteEmulator;
/**
 * @file CustomHashTypes.jsx
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Button, Badge, Modal, Form, Alert, Table } from 'react-bootstrap';
import { toast } from 'react-toastify';
import axios from 'axios';
import AuthUser from '../../../AuthUser';
import Navbar from '../partials/auth/Navbar';
import Sidebar from '../partials/auth/Sidebar';
import TableComponent from '../partials/TableComponent';
import CreateCustomHashType from './partials/CreateCustomHashType';
import TestCustomHashType from './partials/TestCustomHashType';

// Table headers
const columnNames = ["Name", "Type", "Description", "Status", "Usage", "Created"];
const dataIndexes = ["display_name", "type", "description", "status", "usage_count", "created_at"];

const CustomHashTypes = () => {
    const [customHashTypes, setCustomHashTypes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showTestModal, setShowTestModal] = useState(false);
    const [showEditModal, setShowEditModal] = useState(false);
    const [selectedHashType, setSelectedHashType] = useState(null);
    const [editingHashType, setEditingHashType] = useState(null);
    const { http, token, user } = AuthUser();
    
    // Determine sidebar type based on user role
    const sidebarType = user?.role === 'admin' ? 'admin' : 'basic_user';

    useEffect(() => {
        fetchCustomHashTypes();
    }, []);

    const fetchCustomHashTypes = async () => {
        try {
            setLoading(true);
            const response = await http.get('/custom-hash-types');
            setCustomHashTypes(response.data.data || response.data);
        } catch (error) {
            console.error('Error fetching custom hash types:', error);
            toast.error('Failed to fetch custom hash types');
        } finally {
            setLoading(false);
        }
    };

    const handleCreate = async (hashTypeData) => {
        try {
            const response = await http.post('/custom-hash-types', hashTypeData);
            setCustomHashTypes(prev => [response.data, ...prev]);
            setShowCreateModal(false);
            toast.success('Custom hash type created successfully');
        } catch (error) {
            console.error('Error creating custom hash type:', error);
            toast.error('Failed to create custom hash type');
        }
    };

    const handleUpdate = async (hashTypeData) => {
        try {
            const response = await http.put(`/custom-hash-types/${editingHashType.id}`, hashTypeData);
            setCustomHashTypes(prev => 
                prev.map(item => item.id === editingHashType.id ? response.data : item)
            );
            setShowEditModal(false);
            setEditingHashType(null);
            toast.success('Custom hash type updated successfully');
        } catch (error) {
            console.error('Error updating custom hash type:', error);
            toast.error('Failed to update custom hash type');
        }
    };

    const handleDelete = async (hashType) => {
        if (window.confirm(`Are you sure you want to delete "${hashType.display_name}"?`)) {
            try {
                await http.delete(`/custom-hash-types/${hashType.id}`);
                setCustomHashTypes(prev => prev.filter(item => item.id !== hashType.id));
                toast.success('Custom hash type deleted successfully');
            } catch (error) {
                console.error('Error deleting custom hash type:', error);
                toast.error('Failed to delete custom hash type');
            }
        }
    };

    // Prepare data for TableComponent
    const prepareTableData = (hashTypes) => {
        return hashTypes.map(hashType => ({
            ...hashType,
            status: getStatusText(hashType),
            created_at: new Date(hashType.created_at).toLocaleDateString(),
            usage_count: `${hashType.usage_count} times`
        }));
    };

    const getStatusText = (hashType) => {
        const status = [];
        if (hashType.is_active) status.push('Active');
        else status.push('Inactive');
        if (hashType.is_public) status.push('Public');
        else status.push('Private');
        return status.join(', ');
    };

    const buttons = new Map([
        ["createButton", { name: "Create New", funct_call: () => setShowCreateModal(true) }],
        ["startButton", (hashType) => handleTest(hashType)],
        ["updateButton", (hashType) => handleEdit(hashType)],
        ["deleteButton", (hashType) => handleDelete(hashType)]
    ]);

    const handleTest = (hashType) => {
        setSelectedHashType(hashType);
        setShowTestModal(true);
    };

    const handleEdit = (hashType) => {
        setEditingHashType(hashType);
        setShowEditModal(true);
    };

    const getTypeBadgeColor = (type) => {
        switch (type) {
            case 'simple_tls': return 'primary';
            case 'custom_algorithm': return 'success';
            case 'python_script': return 'warning';
            default: return 'secondary';
        }
    };

    const getStatusBadge = (hashType) => {
        if (!hashType.is_active) {
            return <Badge bg="danger">Inactive</Badge>;
        }
        if (hashType.is_public) {
            return <Badge bg="info">Public</Badge>;
        }
        return <Badge bg="secondary">Private</Badge>;
    };

    if (loading) {
        return (
            <Container className="py-4">
                <div className="text-center">
                    <div className="spinner-border" role="status">
                        <span className="visually-hidden">Loading...</span>
                    </div>
                </div>
            </Container>
        );
    }

    return (
        <div className="CustomHashTypes container-fluid">
            <div className="row">
                <Sidebar sidebarType={sidebarType} />
                <div className="col-md-10 px-0">
                    <Navbar />
                    <div className="page container-fluid pt-md-3 px-4">
                        <TableComponent
                            data={prepareTableData(customHashTypes)}
                            dataIndexes={dataIndexes}
                            columnNames={columnNames}
                            buttons={buttons}
                            tableName={"Custom Hash Types"}
                            title={"Custom Hash Types"}
                            description={"Create and manage custom hash types for advanced fingerprinting"}
                        />

            {/* Create Modal */}
            <CreateCustomHashType
                show={showCreateModal}
                onHide={() => setShowCreateModal(false)}
                onSubmit={handleCreate}
            />

            {/* Edit Modal */}
            {editingHashType && (
                <CreateCustomHashType
                    show={showEditModal}
                    onHide={() => {
                        setShowEditModal(false);
                        setEditingHashType(null);
                    }}
                    onSubmit={handleUpdate}
                    editingHashType={editingHashType}
                />
            )}

            {/* Test Modal */}
            {selectedHashType && (
                <TestCustomHashType
                    show={showTestModal}
                    onHide={() => {
                        setShowTestModal(false);
                        setSelectedHashType(null);
                    }}
                    hashType={selectedHashType}
                />
            )}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default CustomHashTypes;


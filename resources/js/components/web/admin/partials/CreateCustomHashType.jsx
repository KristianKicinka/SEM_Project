/**
 * @file CreateCustomHashType.jsx
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

import React, { useState, useEffect } from 'react';
import { Modal, Form, Button, Row, Col, Alert, Card } from 'react-bootstrap';

const CreateCustomHashType = ({ show, onHide, onSubmit, editingHashType }) => {
    const [formData, setFormData] = useState({
        name: '',
        display_name: '',
        description: '',
        type: 'simple_tls',
        configuration: {},
        is_public: false,
        is_active: true
    });
    const [scriptFile, setScriptFile] = useState(null);
    const [errors, setErrors] = useState({});
    const [configurationFields, setConfigurationFields] = useState([]);

    useEffect(() => {
        if (editingHashType) {
            setFormData({
                name: editingHashType.name,
                display_name: editingHashType.display_name,
                description: editingHashType.description || '',
                type: editingHashType.type,
                configuration: editingHashType.configuration || {},
                is_public: editingHashType.is_public,
                is_active: editingHashType.is_active
            });
        } else {
            setFormData({
                name: '',
                display_name: '',
                description: '',
                type: 'simple_tls',
                configuration: {},
                is_public: false,
                is_active: true
            });
        }
        setScriptFile(null);
        setErrors({});
    }, [show, editingHashType]);

    useEffect(() => {
        updateConfigurationFields();
    }, [formData.type]);

    const updateConfigurationFields = () => {
        switch (formData.type) {
            case 'simple_tls':
                setConfigurationFields([
                    { 
                        key: 'fields', 
                        type: 'array', 
                        label: 'TLS Fields', 
                        options: [
                            'version', 'ciphers', 'extensions', 'compression_methods', 
                            'supported_versions', 'signature_algorithms', 'elliptic_curves', 
                            'ec_point_formats', 'alpn_protocols', 'sni', 'timestamp'
                        ]
                    }
                ]);
                break;
            case 'custom_algorithm':
                setConfigurationFields([
                    { key: 'algorithm', type: 'select', label: 'Hash Algorithm', options: ['md5', 'sha1', 'sha256', 'sha512'] },
                    { 
                        key: 'fields', 
                        type: 'array', 
                        label: 'Fields', 
                        options: [
                            // IP layer fields
                            'ip_src', 'ip_dst', 'ip_proto', 'ip_ttl', 'ip_tos', 'ip_flags', 'ip_id', 'ip_len',
                            // TCP layer fields
                            'port_src', 'port_dst', 'tcp_flags', 'tcp_seq', 'tcp_ack', 'tcp_window', 'tcp_urgptr',
                            // UDP layer fields
                            'udp_sport', 'udp_dport', 'udp_len',
                            // TLS layer fields
                            'tls_version', 'tls_content_type', 'tls_length',
                            // Ethernet layer fields
                            'eth_src', 'eth_dst', 'eth_type',
                            // Custom fields
                            'packet_hash', 'payload_size', 'layer_count', 'timestamp', 'packet_size', 'sni'
                        ]
                    }
                ]);
                break;
            case 'python_script':
                setConfigurationFields([]);
                break;
            default:
                setConfigurationFields([]);
        }
    };

    const handleInputChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value
        }));
    };

    const handleConfigurationChange = (key, value) => {
        setFormData(prev => ({
            ...prev,
            configuration: {
                ...prev.configuration,
                [key]: value
            }
        }));
    };

    const handleArrayFieldChange = (key, values) => {
        handleConfigurationChange(key, values);
    };

    const handleFileChange = (e) => {
        setScriptFile(e.target.files[0]);
    };

    const validateForm = () => {
        const newErrors = {};

        if (!formData.name.trim()) {
            newErrors.name = 'Name is required';
        } else if (!/^[A-Z_][A-Z0-9_]*$/.test(formData.name)) {
            newErrors.name = 'Name must be uppercase with underscores only';
        }

        if (!formData.display_name.trim()) {
            newErrors.display_name = 'Display name is required';
        }

        if (formData.type === 'python_script' && !scriptFile && !editingHashType) {
            newErrors.script_file = 'Python script file is required';
        }

        // Validate configuration based on type
        if (formData.type === 'simple_tls' && (!formData.configuration.fields || formData.configuration.fields.length === 0)) {
            newErrors.configuration = 'At least one TLS field must be selected';
        }

        if (formData.type === 'custom_algorithm') {
            if (!formData.configuration.algorithm) {
                newErrors.configuration = 'Hash algorithm is required';
            }
            if (!formData.configuration.fields || formData.configuration.fields.length === 0) {
                newErrors.configuration = 'At least one field must be selected';
            }
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }

        const submitData = {
            name: formData.name,
            display_name: formData.display_name,
            description: formData.description,
            type: formData.type,
            configuration: formData.configuration,
            is_public: formData.is_public || false,
            is_active: formData.is_active !== undefined ? formData.is_active : true
        };

        // Handle script file upload separately if needed
        if (scriptFile) {
            // For now, we'll handle this in the parent component
            // or we can create a separate endpoint for file uploads
            console.warn('Script file upload not yet implemented for JSON requests');
        }

        onSubmit(submitData);
    };

    const renderConfigurationField = (field) => {
        switch (field.type) {
            case 'select':
                return (
                    <Form.Group className="mb-3">
                        <Form.Label>{field.label}</Form.Label>
                        <Form.Select
                            value={formData.configuration[field.key] || ''}
                            onChange={(e) => handleConfigurationChange(field.key, e.target.value)}
                        >
                            <option value="">Select {field.label}</option>
                            {field.options.map(option => (
                                <option key={option} value={option}>{option.toUpperCase()}</option>
                            ))}
                        </Form.Select>
                    </Form.Group>
                );
            case 'array':
                return (
                    <Form.Group className="mb-3">
                        <Form.Label>{field.label}</Form.Label>
                        <div className="border rounded p-3">
                            <Row>
                                {field.options.map((option, index) => (
                                    <Col md={6} lg={4} key={option}>
                                        <Form.Check
                                            type="checkbox"
                                            id={`${field.key}_${option}`}
                                            label={option}
                                            checked={formData.configuration[field.key]?.includes(option) || false}
                                            onChange={(e) => {
                                                const currentValues = formData.configuration[field.key] || [];
                                                const newValues = e.target.checked
                                                    ? [...currentValues, option]
                                                    : currentValues.filter(v => v !== option);
                                                handleArrayFieldChange(field.key, newValues);
                                            }}
                                        />
                                    </Col>
                                ))}
                            </Row>
                        </div>
                    </Form.Group>
                );
            default:
                return null;
        }
    };

    return (
        <Modal show={show} onHide={onHide} size="lg" centered>
            <Modal.Header closeButton>
                <Modal.Title>
                    {editingHashType ? 'Edit Custom Hash Type' : 'Create Custom Hash Type'}
                </Modal.Title>
            </Modal.Header>
            <Form onSubmit={handleSubmit}>
                <Modal.Body>
                    <Row>
                        <Col md={6}>
                            <Form.Group className="mb-3">
                                <Form.Label>Name *</Form.Label>
                                <Form.Control
                                    type="text"
                                    name="name"
                                    value={formData.name}
                                    onChange={handleInputChange}
                                    placeholder="CUSTOM_TLS_SIMPLE"
                                    isInvalid={!!errors.name}
                                />
                                <Form.Control.Feedback type="invalid">
                                    {errors.name}
                                </Form.Control.Feedback>
                                <Form.Text className="text-muted">
                                    Unique identifier (uppercase with underscores)
                                </Form.Text>
                            </Form.Group>
                        </Col>
                        <Col md={6}>
                            <Form.Group className="mb-3">
                                <Form.Label>Display Name *</Form.Label>
                                <Form.Control
                                    type="text"
                                    name="display_name"
                                    value={formData.display_name}
                                    onChange={handleInputChange}
                                    placeholder="Custom TLS Simple"
                                    isInvalid={!!errors.display_name}
                                />
                                <Form.Control.Feedback type="invalid">
                                    {errors.display_name}
                                </Form.Control.Feedback>
                            </Form.Group>
                        </Col>
                    </Row>

                    <Form.Group className="mb-3">
                        <Form.Label>Description</Form.Label>
                        <Form.Control
                            as="textarea"
                            rows={3}
                            name="description"
                            value={formData.description}
                            onChange={handleInputChange}
                            placeholder="Describe what this custom hash type does..."
                        />
                    </Form.Group>

                    <Form.Group className="mb-3">
                        <Form.Label>Type *</Form.Label>
                        <Form.Select
                            name="type"
                            value={formData.type}
                            onChange={handleInputChange}
                        >
                            <option value="simple_tls">Simple TLS Hash</option>
                            <option value="custom_algorithm">Custom Algorithm Hash</option>
                            <option value="python_script">Python Script Hash</option>
                        </Form.Select>
                    </Form.Group>

                    {formData.type === 'python_script' && (
                        <Form.Group className="mb-3">
                            <Form.Label>Python Script File {!editingHashType && '*'}</Form.Label>
                            <Form.Control
                                type="file"
                                accept=".py"
                                onChange={handleFileChange}
                                isInvalid={!!errors.script_file}
                            />
                            <Form.Control.Feedback type="invalid">
                                {errors.script_file}
                            </Form.Control.Feedback>
                            <Form.Text className="text-muted">
                                Upload a Python script that implements generate_hash(packet, sni, **kwargs) function
                            </Form.Text>
                        </Form.Group>
                    )}

                    {configurationFields.length > 0 && (
                        <Card className="mb-3">
                            <Card.Header>
                                <h6 className="mb-0">Configuration</h6>
                            </Card.Header>
                            <Card.Body>
                                {configurationFields.map(field => (
                                    <div key={field.key}>
                                        {renderConfigurationField(field)}
                                    </div>
                                ))}
                                {errors.configuration && (
                                    <Alert variant="danger" className="mt-2">
                                        {errors.configuration}
                                    </Alert>
                                )}
                            </Card.Body>
                        </Card>
                    )}

                    <Row>
                        <Col md={6}>
                            <Form.Check
                                type="checkbox"
                                id="is_public"
                                label="Make Public"
                                name="is_public"
                                checked={formData.is_public || false}
                                onChange={handleInputChange}
                            />
                            <Form.Text className="text-muted">
                                Allow other users to use this hash type
                            </Form.Text>
                        </Col>
                        <Col md={6}>
                            <Form.Check
                                type="checkbox"
                                id="is_active"
                                label="Active"
                                name="is_active"
                                checked={formData.is_active || false}
                                onChange={handleInputChange}
                            />
                            <Form.Text className="text-muted">
                                Enable this hash type for use
                            </Form.Text>
                        </Col>
                    </Row>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={onHide}>
                        Cancel
                    </Button>
                    <Button variant="warning" type="submit" className="bg-orange text-white">
                        {editingHashType ? 'Update' : 'Create'}
                    </Button>
                </Modal.Footer>
            </Form>
        </Modal>
    );
};

export default CreateCustomHashType;


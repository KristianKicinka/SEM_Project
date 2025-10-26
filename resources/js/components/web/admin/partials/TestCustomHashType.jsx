/**
 * @file TestCustomHashType.jsx
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

import React, { useState } from 'react';
import { Modal, Form, Button, Row, Col, Alert, Card, ProgressBar } from 'react-bootstrap';
import { toast } from 'react-toastify';
import axios from 'axios';
import AuthUser from '../../../../AuthUser';

const TestCustomHashType = ({ show, onHide, hashType }) => {
    const [apkFiles, setApkFiles] = useState([]);
    const [packageNames, setPackageNames] = useState('');
    const [testing, setTesting] = useState(false);
    const [results, setResults] = useState([]);
    const [progress, setProgress] = useState(0);
    const { http } = AuthUser();

    const handleApkFilesChange = (e) => {
        setApkFiles(Array.from(e.target.files));
    };

    const handlePackageNamesChange = (e) => {
        setPackageNames(e.target.value);
    };

    const handleTest = async (e) => {
        e.preventDefault();
        
        if (apkFiles.length === 0 && !packageNames.trim()) {
            toast.error('Please provide APK files or package names to test');
            return;
        }

        setTesting(true);
        setResults([]);
        setProgress(0);

        try {
            const formData = new FormData();
            
            // Add APK files
            apkFiles.forEach(file => {
                formData.append('apk_files[]', file);
            });
            
            // Add package names
            if (packageNames.trim()) {
                formData.append('package_names', packageNames);
            }

            // Simulate progress
            const progressInterval = setInterval(() => {
                setProgress(prev => Math.min(prev + 10, 90));
            }, 500);

            const response = await http.post(`/custom-hash-types/${hashType.id}/test`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            clearInterval(progressInterval);
            setProgress(100);

            setResults(response.data.results || []);
            toast.success('Test completed successfully');

        } catch (error) {
            console.error('Test error:', error);
            toast.error('Test failed: ' + (error.response?.data?.error || error.message));
        } finally {
            setTesting(false);
            setProgress(0);
        }
    };

    const resetForm = () => {
        setApkFiles([]);
        setPackageNames('');
        setResults([]);
        setProgress(0);
    };

    const handleClose = () => {
        resetForm();
        onHide();
    };

    const getStatusBadge = (result) => {
        if (result.status === 'success') {
            return <span className="badge bg-success">Success</span>;
        } else if (result.status === 'error') {
            return <span className="badge bg-danger">Error</span>;
        } else {
            return <span className="badge bg-warning">Warning</span>;
        }
    };

    return (
        <Modal show={show} onHide={handleClose} size="xl" centered>
            <Modal.Header closeButton>
                <Modal.Title>Test Custom Hash Type: {hashType.display_name}</Modal.Title>
            </Modal.Header>
            <Form onSubmit={handleTest}>
                <Modal.Body>
                    <Row>
                        <Col md={6}>
                            <Card className="mb-3">
                                <Card.Header>
                                    <h6 className="mb-0">APK Files</h6>
                                </Card.Header>
                                <Card.Body>
                                    <Form.Group>
                                        <Form.Label>Upload APK Files</Form.Label>
                                        <Form.Control
                                            type="file"
                                            multiple
                                            accept=".apk"
                                            onChange={handleApkFilesChange}
                                            disabled={testing}
                                        />
                                        <Form.Text className="text-muted">
                                            Select one or more APK files to test
                                        </Form.Text>
                                    </Form.Group>
                                    {apkFiles.length > 0 && (
                                        <div className="mt-2">
                                            <small className="text-muted">Selected files:</small>
                                            <ul className="list-unstyled">
                                                {apkFiles.map((file, index) => (
                                                    <li key={index} className="small">
                                                        <i className="fas fa-file me-1"></i>
                                                        {file.name}
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    )}
                                </Card.Body>
                            </Card>
                        </Col>
                        <Col md={6}>
                            <Card className="mb-3">
                                <Card.Header>
                                    <h6 className="mb-0">Package Names</h6>
                                </Card.Header>
                                <Card.Body>
                                    <Form.Group>
                                        <Form.Label>Package Names (one per line)</Form.Label>
                                        <Form.Control
                                            as="textarea"
                                            rows={6}
                                            value={packageNames}
                                            onChange={handlePackageNamesChange}
                                            placeholder="com.example.app1&#10;com.example.app2&#10;com.example.app3"
                                            disabled={testing}
                                        />
                                        <Form.Text className="text-muted">
                                            Enter package names, one per line
                                        </Form.Text>
                                    </Form.Group>
                                </Card.Body>
                            </Card>
                        </Col>
                    </Row>

                    {testing && (
                        <Alert variant="info" className="mb-3">
                            <div className="d-flex align-items-center">
                                <div className="spinner-border spinner-border-sm me-2" role="status">
                                    <span className="visually-hidden">Testing...</span>
                                </div>
                                Testing your custom hash type...
                            </div>
                            <ProgressBar now={progress} className="mt-2" />
                        </Alert>
                    )}

                    {results.length > 0 && (
                        <Card>
                            <Card.Header>
                                <h6 className="mb-0">Test Results</h6>
                            </Card.Header>
                            <Card.Body>
                                {results.map((result, index) => (
                                    <div key={index} className="border rounded p-3 mb-3">
                                        <div className="d-flex justify-content-between align-items-center mb-2">
                                            <h6 className="mb-0">
                                                {result.type === 'apk' ? (
                                                    <>
                                                        <i className="fas fa-mobile-alt me-2"></i>
                                                        {result.filename}
                                                    </>
                                                ) : (
                                                    <>
                                                        <i className="fas fa-box me-2"></i>
                                                        {result.package_name}
                                                    </>
                                                )}
                                            </h6>
                                            {getStatusBadge(result)}
                                        </div>
                                        <div className="row">
                                            <div className="col-md-6">
                                                <small className="text-muted">Status:</small>
                                                <p className="mb-1">{result.result?.status || 'Unknown'}</p>
                                            </div>
                                            <div className="col-md-6">
                                                <small className="text-muted">Message:</small>
                                                <p className="mb-1">{result.result?.message || 'No message'}</p>
                                            </div>
                                        </div>
                                        {result.result?.hashes_generated && (
                                            <div className="row">
                                                <div className="col-md-6">
                                                    <small className="text-muted">Hashes Generated:</small>
                                                    <p className="mb-1">{result.result.hashes_generated}</p>
                                                </div>
                                                <div className="col-md-6">
                                                    <small className="text-muted">Hash Type Used:</small>
                                                    <p className="mb-1">{result.result.custom_hash_type_used}</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </Card.Body>
                        </Card>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={handleClose} disabled={testing}>
                        Close
                    </Button>
                    <Button 
                        variant="warning" 
                        className="bg-orange text-white"
                        type="submit" 
                        disabled={testing || (apkFiles.length === 0 && !packageNames.trim())}
                    >
                        {testing ? (
                            <>
                                <span className="spinner-border spinner-border-sm me-2" role="status">
                                    <span className="visually-hidden">Testing...</span>
                                </span>
                                Testing...
                            </>
                        ) : (
                            'Run Test'
                        )}
                    </Button>
                </Modal.Footer>
            </Form>
        </Modal>
    );
};

export default TestCustomHashType;


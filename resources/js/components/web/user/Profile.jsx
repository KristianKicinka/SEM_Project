/**
 * @file Profile.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";
import { Link } from "react-router-dom";
import { Card, Row, Col, Button, Form, Alert, Badge, Modal } from "react-bootstrap";

import AuthUser from "../../../AuthUser";
import Navbar from "../partials/auth/Navbar";
import Sidebar from "../partials/auth/Sidebar";
import PageHeader from "../partials/PageHeader";

const Profile = () => {
    const { http, setToken, user, editUser } = AuthUser();
    const [apiKey, setApiKey] = useState("");
    
    // Form states
    const [name, setName] = useState(user.name);
    const [surname, setSurname] = useState(user.surname);
    const [email, setEmail] = useState(user.email);
    const [phone, setPhone] = useState(user.phone);
    const [password, setPassword] = useState("");
    const [passwordRe, setPasswordRe] = useState("");
    
    // Track changes
    const [hasChanges, setHasChanges] = useState(false);
    const [originalData, setOriginalData] = useState({
        name: user.name,
        surname: user.surname,
        email: user.email,
        phone: user.phone
    });
    
    // Profile photo states
    const [profilePhoto, setProfilePhoto] = useState(null);
    const [profilePhotoPreview, setProfilePhotoPreview] = useState(() => {
        if (user?.profile_photo) {
            return user.profile_photo.startsWith('http') ? user.profile_photo : `/storage/${user.profile_photo}`;
        }
        return null;
    });
    
    // UI states
    const [errors, setErrors] = useState({});
    const [success, setSuccess] = useState("");
    const [fetchDataState, setFetchDataState] = useState(false);
    const [editingField, setEditingField] = useState(null);
    const [loading, setLoading] = useState(false);
    
    // Modal states
    const [showPasswordModal, setShowPasswordModal] = useState(false);
    const [showApiKeyModal, setShowApiKeyModal] = useState(false);

    /**
     * @brief Check if data has changed
     */
    const checkForChanges = () => {
        const currentData = { name, surname, email, phone };
        const changed = Object.keys(currentData).some(key => 
            currentData[key] !== originalData[key]
        );
        setHasChanges(changed);
    };

    /**
     * @brief Handle field change
     */
    const handleFieldChange = (field, value) => {
        switch(field) {
            case 'name': setName(value); break;
            case 'surname': setSurname(value); break;
            case 'email': setEmail(value); break;
            case 'phone': setPhone(value); break;
        }
    };

    /**
     * @brief Save all changes
     */
    const saveAllChanges = async () => {
        if (!hasChanges) return;

        setLoading(true);
        setErrors({});
        setSuccess("");

        const userData = {
            user_id: user.id,
            name,
            surname,
            email,
            phone
        };

        try {
            const resp = await http.post('/user/edit', userData);
            editUser(resp.data);
            setOriginalData({ name, surname, email, phone });
            setHasChanges(false);
            setSuccess("Profile updated successfully!");
        } catch (error) {
            if (error.response?.status === 400) {
                setErrors(error.response.data.errors);
            }
            console.log(error);
        } finally {
            setLoading(false);
        }
    };


    /**
     * @brief Change password
     */
    const changePassword = async (e) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});
        setSuccess("");

        const userData = {
            user_id: user.id,
            password: password,
            password_re: passwordRe
        };

        try {
            await http.post('/user/change-password', userData);
            setSuccess("Password changed successfully!");
            setPassword("");
            setPasswordRe("");
            setShowPasswordModal(false);
        } catch (error) {
            if (error.response?.status === 400) {
                setErrors(error.response.data.errors);
            }
            console.log(error);
        } finally {
            setLoading(false);
        }
    };

    /**
     * @brief Generate API key
     */
    const generateApiKey = async () => {
        setLoading(true);
        try {
            const resp = await http.post('/user/api-key-generate', { user_id: user.id });
            setApiKey(resp.data.api_auth_key || "");
            setShowApiKeyModal(true);
        } catch (error) {
            console.log(error);
        } finally {
            setLoading(false);
        }
    };

    /**
     * @brief Handle profile photo upload
     */
    const handleProfilePhotoChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setProfilePhoto(file);
            const reader = new FileReader();
            reader.onload = (e) => {
                setProfilePhotoPreview(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    };

    /**
     * @brief Upload profile photo
     */
    const uploadProfilePhoto = async () => {
        if (!profilePhoto) return;

        setLoading(true);
        const formData = new FormData();
        formData.append('profile_photo', profilePhoto);

        try {
            const response = await http.post('/user/profile-photo', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            
            editUser(response.data.user);
            // Update preview with correct URL
            const newPhotoPath = response.data.user.profile_photo;
            setProfilePhotoPreview(
                newPhotoPath.startsWith('http') ? newPhotoPath : `/storage/${newPhotoPath}`
            );
            setSuccess("Profile photo updated successfully!");
            setProfilePhoto(null);
        } catch (error) {
            if (error.response?.status === 400) {
                setErrors(error.response.data.errors);
            }
            console.log(error);
        } finally {
            setLoading(false);
        }
    };

    /**
     * @brief Fetch user data
     */
    const fetchData = async () => {
        try {
            const resp = await http.post('/user/get-api-key', { user_id: user.id });
            setApiKey(resp.data.api_auth_key || "");
        } catch (error) {
            console.log(error);
        }
    };

    useEffect(() => {
        fetchData();
    }, [fetchDataState]);

    // Check for changes whenever form data changes
    useEffect(() => {
        checkForChanges();
    }, [name, surname, email, phone]);

    // Update profile photo preview when user changes
    useEffect(() => {
        if (user?.profile_photo) {
            const photoUrl = user.profile_photo.startsWith('http') ? user.profile_photo : `/storage/${user.profile_photo}`;
            setProfilePhotoPreview(photoUrl);
        } else {
            setProfilePhotoPreview(null);
        }
    }, [user?.profile_photo]);

    return (
        <div className="Profile container-fluid">
            <div className="row">
                <Sidebar sidebarType="basic_user" />
                <div className="col-md-10 px-0">
                    <Navbar />
                    <div className="page container-fluid pt-md-3 px-4">
                        <div className="container-fluid shadow bg-white text-dark p-3">
                            <PageHeader 
                                title="Profile Settings"
                                description="Manage your personal information and account settings"
                            />

                            {/* Success/Error Messages */}
                            {success && (
                                <Alert variant="success" dismissible onClose={() => setSuccess("")}>
                                    <i className="fas fa-check-circle me-2"></i>
                                    {success}
                                </Alert>
                            )}

                            {Object.keys(errors).length > 0 && (
                                <Alert variant="danger" dismissible onClose={() => setErrors({})}>
                                    <i className="fas fa-exclamation-triangle me-2"></i>
                                    Please correct the following errors:
                                    <ul className="mb-0 mt-2">
                                        {Object.values(errors).flat().map((error, index) => (
                                            <li key={index}>{error}</li>
                                        ))}
                                    </ul>
                                </Alert>
                            )}


                            <Row className="g-4 px-4 py-4">
                                {/* Profile Photo Section */}
                                <Col lg={4}>
                                    <Card className="h-100 shadow-sm">
                                        <Card.Header className="bg-orange text-white">
                                            <h5 className="mb-0">
                                                <i className="fas fa-camera me-2"></i>
                                                Profile Photo
                                            </h5>
                                        </Card.Header>
                                        <Card.Body className="text-center">
                                            <div className="mb-4">
                                                {profilePhotoPreview ? (
                                                    <img 
                                                        src={profilePhotoPreview} 
                                                        alt="Profile" 
                                                        className="rounded-circle border border-3 border-orange" 
                                                        style={{width: '120px', height: '120px', objectFit: 'cover'}}
                                                    />
                                                ) : (
                                                    <div className="bg-light rounded-circle border border-3 border-orange mx-auto d-flex align-items-center justify-content-center" 
                                                         style={{width: '120px', height: '120px'}}>
                                                        <i className="fas fa-user fa-3x text-muted"></i>
                                                    </div>
                                                )}
                                            </div>
                                            
                                            <Form.Group className="mb-3">
                                                <Form.Control
                                                    type="file"
                                                    accept="image/*"
                                                    onChange={handleProfilePhotoChange}
                                                    className="form-control-sm"
                                                />
                                                <Form.Text className="text-muted">
                                                    Max size: 2MB. Supported: JPG, PNG, GIF
                                                </Form.Text>
                                            </Form.Group>
                                            
                                            {profilePhoto && (
                                                <Button 
                                                    variant="search-outline" 
                                                    className="btn-search-outline text-orange" 
                                                    size="sm" 
                                                    onClick={uploadProfilePhoto}
                                                    disabled={loading}
                                                >
                                                    {loading ? (
                                                        <>
                                                            <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                                                            Uploading...
                                                        </>
                                                    ) : (
                                                        <>
                                                            <i className="fas fa-upload me-1"></i>
                                                            Upload Photo
                                                        </>
                                                    )}
                                                </Button>
                                            )}
                                        </Card.Body>
                                    </Card>
                                </Col>

                                {/* Personal Information */}
                                <Col lg={8}>
                                    <Card className="shadow-sm">
                                        <Card.Header className="bg-light">
                                            <h5 className="mb-0">
                                                <i className="fas fa-user me-2"></i>
                                                Personal Information
                                            </h5>
                                        </Card.Header>
                                        <Card.Body>
                                            <Row className="g-3">
                                                {/* Name */}
                                                <Col md={6}>
                                                    <Form.Group>
                                                        <Form.Label className="fw-bold">
                                                            <i className="fas fa-user me-1"></i>
                                                            First Name
                                                        </Form.Label>
                                                        <Form.Control
                                                            type="text"
                                                            value={name}
                                                            onChange={(e) => handleFieldChange('name', e.target.value)}
                                                            className={errors.name ? 'is-invalid' : ''}
                                                        />
                                                        {errors.name && (
                                                            <div className="invalid-feedback">{errors.name[0]}</div>
                                                        )}
                                                    </Form.Group>
                                                </Col>

                                                {/* Surname */}
                                                <Col md={6}>
                                                    <Form.Group>
                                                        <Form.Label className="fw-bold">
                                                            <i className="fas fa-user me-1"></i>
                                                            Last Name
                                                        </Form.Label>
                                                        <Form.Control
                                                            type="text"
                                                            value={surname}
                                                            onChange={(e) => handleFieldChange('surname', e.target.value)}
                                                            className={errors.surname ? 'is-invalid' : ''}
                                                        />
                                                        {errors.surname && (
                                                            <div className="invalid-feedback">{errors.surname[0]}</div>
                                                        )}
                                                    </Form.Group>
                                                </Col>

                                                {/* Email */}
                                                <Col md={6}>
                                                    <Form.Group>
                                                        <Form.Label className="fw-bold">
                                                            <i className="fas fa-envelope me-1"></i>
                                                            Email Address
                                                        </Form.Label>
                                                        <Form.Control
                                                            type="email"
                                                            value={email}
                                                            onChange={(e) => handleFieldChange('email', e.target.value)}
                                                            className={errors.email ? 'is-invalid' : ''}
                                                        />
                                                        {errors.email && (
                                                            <div className="invalid-feedback">{errors.email[0]}</div>
                                                        )}
                                                    </Form.Group>
                                                </Col>

                                                {/* Phone */}
                                                <Col md={6}>
                                                    <Form.Group>
                                                        <Form.Label className="fw-bold">
                                                            <i className="fas fa-phone me-1"></i>
                                                            Phone Number
                                                        </Form.Label>
                                                        <Form.Control
                                                            type="tel"
                                                            value={phone}
                                                            onChange={(e) => handleFieldChange('phone', e.target.value)}
                                                            className={errors.phone ? 'is-invalid' : ''}
                                                        />
                                                        {errors.phone && (
                                                            <div className="invalid-feedback">{errors.phone[0]}</div>
                                                        )}
                                                    </Form.Group>
                                                </Col>
                                            </Row>
                                        </Card.Body>
                                    </Card>

                                    {/* Security Section */}
                                    <Card className="mt-4 shadow-sm">
                                        <Card.Header className="bg-light">
                                            <h5 className="mb-0">
                                                <i className="fas fa-shield-alt me-2"></i>
                                                Security
                                            </h5>
                                        </Card.Header>
                                        <Card.Body>
                                            <Row className="g-3">
                                                <Col md={6}>
                                                    <div className="d-flex align-items-center justify-content-between p-3 border rounded">
                                                        <div>
                                                            <h6 className="mb-1">Password</h6>
                                                            <small className="text-muted">Last updated: Recently</small>
                                                        </div>
                                                        <Button
                                                            className="btn-search text-light"
                                                            size="sm"
                                                            onClick={() => setShowPasswordModal(true)}
                                                        >
                                                            <i className="fas fa-key me-1"></i>
                                                            Change
                                                        </Button>
                                                    </div>
                                                </Col>

                                                <Col md={6}>
                                                    <div className="d-flex align-items-center justify-content-between p-3 border rounded">
                                                        <div>
                                                            <h6 className="mb-1">API Key</h6>
                                                            <small className="text-muted">
                                                                {apiKey ? 'Active' : 'Not generated'}
                                                            </small>
                                                        </div>
                                                        <div className="d-flex gap-2">
                                                            {apiKey && (
                                                                <Button
                                                                    variant="search-outline text-orange"
                                                                    size="sm"
                                                                    onClick={() => setShowApiKeyModal(true)}
                                                                >
                                                                    <i className="fas fa-eye me-1"></i>
                                                                    View
                                                                </Button>
                                                            )}
                                                            <Button
                                                                className="btn-search text-light"
                                                                size="sm"
                                                                onClick={generateApiKey}
                                                                disabled={loading}
                                                            >
                                                                <i className="fas fa-code me-1"></i>
                                                                {apiKey ? 'Regenerate' : 'Generate'}
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </Col>
                                            </Row>
                                        </Card.Body>
                                    </Card>
                                </Col>
                            </Row>

                            {/* Save All Button */}
                            <Row className="mt-2 px-4">
                                <Col>
                                    <div className="d-flex justify-content-end">
                                        <Button 
                                            className="btn-search text-light" 
                                            size="md" 
                                            onClick={saveAllChanges}
                                            disabled={loading || !hasChanges}
                                        >
                                            {loading ? (
                                                <>
                                                    <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                                                    Saving...
                                                </>
                                            ) : (
                                                <>
                                                    <i className="fas fa-save me-2"></i>
                                                    Save All Changes
                                                </>
                                            )}
                                        </Button>
                                    </div>
                                </Col>
                            </Row>
                        </div>
                    </div>
                </div>
            </div>

            {/* Password Change Modal */}
            <Modal show={showPasswordModal} onHide={() => setShowPasswordModal(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>
                        <i className="fas fa-key me-2"></i>
                        Change Password
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={changePassword}>
                    <Modal.Body>
                        <Form.Group className="mb-3">
                            <Form.Label>New Password</Form.Label>
                            <Form.Control
                                type="password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                className={errors.password ? 'is-invalid' : ''}
                                required
                            />
                            {errors.password && (
                                <div className="invalid-feedback">{errors.password[0]}</div>
                            )}
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Confirm Password</Form.Label>
                            <Form.Control
                                type="password"
                                value={passwordRe}
                                onChange={(e) => setPasswordRe(e.target.value)}
                                className={errors.password_re ? 'is-invalid' : ''}
                                required
                            />
                            {errors.password_re && (
                                <div className="invalid-feedback">{errors.password_re[0]}</div>
                            )}
                        </Form.Group>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowPasswordModal(false)}>
                            Cancel
                        </Button>
                        <Button className="btn-search text-light" type="submit" disabled={loading}>
                            {loading ? (
                                <>
                                    <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Updating...
                                </>
                            ) : (
                                'Update Password'
                            )}
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>

            {/* API Key Modal */}
            <Modal show={showApiKeyModal} onHide={() => setShowApiKeyModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        <i className="fas fa-code me-2"></i>
                        API Key
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Alert variant="info">
                        <i className="fas fa-info-circle me-2"></i>
                        Keep this API key secure. It provides access to your account.
                    </Alert>
                    <Form.Group>
                        <Form.Label>Your API Key:</Form.Label>
                        <Form.Control
                            type="text"
                            value={apiKey}
                            readOnly
                            className="font-monospace"
                        />
                    </Form.Group>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShowApiKeyModal(false)}>
                        Close
                    </Button>
                    <Button 
                        className="btn-search text-light" 
                        onClick={() => navigator.clipboard.writeText(apiKey)}
                    >
                        <i className="fas fa-copy me-1"></i>
                        Copy to Clipboard
                    </Button>
                </Modal.Footer>
            </Modal>
        </div>
    );
};

export default Profile;
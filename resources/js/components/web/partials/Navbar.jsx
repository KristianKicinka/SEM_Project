/**
 * @file Navbar.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React from "react";
import ReactDOM from "react-dom";
import { Link } from "react-router-dom";
import AuthUser from "../../../AuthUser";


const Navbar = () => {
    const { token, user, logout } = AuthUser();

    // Component body
    return (
        <div className="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
            <div className="container px-4">
                <Link className="navbar-brand ps-3" to="/">
                    Mobile apps fingerprints generator
                </Link>
                <button
                    className="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#mainNavigation"
                    aria-controls="mainNavigation"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
                >
                    <span className="navbar-toggler-icon"></span>
                </button>

                <div className="collapse navbar-collapse float-end" id="mainNavigation">
                    <ul className="navbar-nav ms-auto">
                        <li className="nav-item">
                            <Link className="nav-link active" aria-current="page" to="/about" >
                                About project
                            </Link>
                        </li>
                        <li className="nav-item">
                            <Link className="nav-link active" aria-current="page" to="/database" >
                                Fingerprints database
                            </Link>
                        </li>
                        <li className="nav-item">
                            <Link className="nav-link active" aria-current="page" to="/api-info" >
                                API
                            </Link>
                        </li>
                        
                        {/* Show different options based on authentication status */}
                        {token ? (
                            <>
                                {/* Authenticated user options */}
                                        <li className="nav-item dropdown px-3">
                                            <a className="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                {user?.profile_photo ? (
                                                    <img 
                                                        src={user.profile_photo.startsWith('http') ? user.profile_photo : `/storage/${user.profile_photo}`}
                                                        alt="Profile" 
                                                        className="rounded-circle me-2" 
                                                        style={{width: '24px', height: '24px', objectFit: 'cover'}}
                                                    />
                                                ) : (
                                                    <i className="fas fa-user me-1"></i>
                                                )}
                                                {user?.name + ' ' + user?.surname || 'User'}
                                            </a>
                                    <ul className="dropdown-menu" aria-labelledby="navbarDropdown">
                                        <li>
                                            <Link className="dropdown-item" to={user?.role === 'admin' ? '/admin/dashboard' : '/user/dashboard'}>
                                                <i className="fas fa-tachometer-alt me-2"></i>
                                                Dashboard
                                            </Link>
                                        </li>
                                        {user?.role === 'basic_user' && (
                                            <li>
                                                <Link className="dropdown-item" to='/user/profile'>
                                                    <i className="fas fa-user me-2"></i>
                                                    Profile
                                                </Link>
                                            </li>
                                        )}
                                        <li>
                                            <Link className="dropdown-item" to={user?.role === 'admin' ? '/admin/api' : '/user/api'}>
                                                <i className="fas fa-code me-2"></i>
                                                API
                                            </Link>
                                        </li>
                                        <li>
                                            <Link className="dropdown-item" to={user?.role === 'admin' ? '/admin/custom-hash-types' : '/user/custom-hash-types'}>
                                                <i className="fas fa-puzzle-piece me-2"></i>
                                                Custom Hashes
                                            </Link>
                                        </li>
        
                                        
                                        
                                        {user?.role === 'admin' && (
                                            <>
                                                <li><hr className="dropdown-divider" /></li>
                                                <li>
                                                    <Link className="dropdown-item" to="/admin/users">
                                                        <i className="fas fa-users me-2"></i>
                                                        Users
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link className="dropdown-item" to="/admin/hashes">
                                                        <i className="fas fa-hashtag me-2"></i>
                                                        Hashes
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link className="dropdown-item" to="/admin/files">
                                                        <i className="fas fa-file me-2"></i>
                                                        Files
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link className="dropdown-item" to="/admin/emulators">
                                                        <i className="fas fa-server me-2"></i>
                                                        Emulators
                                                    </Link>
                                                </li>
                                            </>
                                        )}
                                        <li><hr className="dropdown-divider" /></li>
                                        <li>
                                            <button className="dropdown-item" onClick={logout}>
                                                <i className="fas fa-sign-out-alt me-2"></i>
                                                Logout
                                            </button>
                                        </li>
                                    </ul>
                                </li>
                            </>
                        ) : (
                            <>
                                {/* Non-authenticated user options */}
                                <li className="nav-item ps-4 pt-1">
                                    <Link className="btn btn-sm btn-search-outline" aria-current="page" to="/login" >
                                        Sign in
                                    </Link>
                                </li>
                                <li className="nav-item ps-2 pt-1">
                                    <Link className="btn btn-sm btn-search text-white" aria-current="page" to="/register" >
                                        Sign up
                                    </Link>
                                </li>
                            </>
                        )}
                    </ul>
                </div>
            </div>
        </div>
    );
};

export default Navbar;

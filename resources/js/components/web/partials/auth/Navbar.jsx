/**
 * @file Navbar.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { useState, useEffect, useRef } from "react";
import ReactDOM from "react-dom";

import { Link } from "react-router-dom";
import AuthUser from "../../../../AuthUser";


const Navbar = () => {

    const {token, logout, user} = AuthUser();
    const [showDropdown, setShowDropdown] = useState(false);
    const dropdownRef = useRef(null);

    /**
     * @brief The function ensures logouting users
     */
    const logoutUser = () => {
        if (token != undefined)
            logout();
    }

    /**
     * @brief The function ensures toggling dropdown menu
     */
    const toggleDropdown = () => {
        setShowDropdown(!showDropdown);
    }

    /**
     * @brief The function ensures getting user initials
     */
    const getUserInitials = () => {
        if (!user) return 'U';
        const firstName = user.name ? user.name.charAt(0).toUpperCase() : '';
        const lastName = user.surname ? user.surname.charAt(0).toUpperCase() : '';
        return firstName + lastName;
    }

    /**
     * @brief The function ensures getting user full name
     */
    const getUserFullName = () => {
        if (!user) return 'User';
        return `${user.name || ''} ${user.surname || ''}`.trim() || 'User';
    }

    /**
     * @brief The function ensures closing dropdown when clicking outside
     */
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setShowDropdown(false);
            }
        };

        if (showDropdown) {
            document.addEventListener('mousedown', handleClickOutside);
        }

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, [showDropdown]);

    // Component body
    return (
        <nav className="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow py-2">
            <div className="container-fluid">
                <div className="row w-100">
                    <div className="col-md-4"></div>
                    <div className="col-md-4"></div>
                    <div className="col-md-4">
                        <ul className="navbar-nav ml-auto float-end">
                            <li className="nav-item dropdown">
                                <div className="dropdown" ref={dropdownRef}>
                                    <button 
                                        className="btn btn-link nav-link d-flex align-items-center text-decoration-none" 
                                        onClick={toggleDropdown}
                                        style={{ border: 'none', background: 'none' }}
                                    >
                                        {/* Profile Photo or Initials */}
                                        <div className="me-2">
                                            {user?.profile_photo ? (
                                                <img 
                                                    src={`/storage/${user.profile_photo}`} 
                                                    alt="Profile" 
                                                    className="rounded-circle"
                                                    style={{ width: '32px', height: '32px', objectFit: 'cover' }}
                                                />
                                            ) : (
                                                <div 
                                                    className="rounded-circle bg-orange text-white d-flex align-items-center justify-content-center"
                                                    style={{ width: '32px', height: '32px', fontSize: '14px', fontWeight: 'bold' }}
                                                >
                                                    {getUserInitials()}
                                                </div>
                                            )}
                                        </div>
                                        
                                        {/* User Name */}
                                        <span className="text-gray-600 small d-none d-lg-inline">
                                            {getUserFullName()}
                                        </span>
                                        
                                        {/* Dropdown Arrow */}
                                        <i className={`fa-solid fa-chevron-down ms-2 small ${showDropdown ? 'rotate-180' : ''}`} 
                                           style={{ transition: 'transform 0.2s ease' }}></i>
                                    </button>
                                    
                                    {/* Dropdown Menu */}
                                    {showDropdown && (
                                        <div className="dropdown-menu dropdown-menu-end show" style={{ minWidth: '200px' }}>
                                            <div className="dropdown-header">
                                                <div className="d-flex align-items-center">
                                                    {user?.profile_photo ? (
                                                        <img 
                                                            src={`/storage/${user.profile_photo}`} 
                                                            alt="Profile" 
                                                            className="rounded-circle me-2"
                                                            style={{ width: '24px', height: '24px', objectFit: 'cover' }}
                                                        />
                                                    ) : (
                                                        <div 
                                                            className="rounded-circle bg-orange text-white d-flex align-items-center justify-content-center me-2"
                                                            style={{ width: '24px', height: '24px', fontSize: '12px', fontWeight: 'bold' }}
                                                        >
                                                            {getUserInitials()}
                                                        </div>
                                                    )}
                                                    <div>
                                                        <div className="fw-bold">{getUserFullName()}</div>
                                                        <small className="text-muted">{user?.email}</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <Link className="dropdown-item" to="/">
                                                <i className="fa-solid fa-hashtag me-2"></i>
                                                Main page
                                            </Link>
                                            <Link className="dropdown-item" to="/user/profile">
                                                <i className="fa-solid fa-user me-2"></i>
                                                Profile
                                            </Link>
                                            <div className="dropdown-divider"></div>
                                            <button 
                                                className="dropdown-item text-danger" 
                                                onClick={logoutUser}
                                            >
                                                <i className="fa-solid fa-right-from-bracket me-2"></i>
                                                Logout
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    );
};

export default Navbar;

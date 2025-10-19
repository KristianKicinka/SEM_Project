/**
 * @file Dashboard.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { useState } from "react";
import ReactDOM from "react-dom";

import Navbar from "../partials/auth/Navbar";
import Sidebar from "../partials/auth/Sidebar";
import PageHeader from "../partials/PageHeader";

import { Link } from "react-router-dom";


const Dashboard = () => {

    // Component body
    return (
        <div className="Dashboard container-fluid">
            <div className="row d-flex">
                <Sidebar sidebarType="admin" />
                <div className="col px-0" style={{flex: '1'}}>
                    <Navbar />
                    <div className="page container-fluid pt-md-3 px-4">
                       <div className="container-fluid shadow bg-white text-dark p-3">
                            <PageHeader 
                                title="Dashboard"
                                description="Welcome to admin panel. There you can manage web application settings like app users, created hashes, server emulators or API interface."
                            />
                            <div className="row p-3"></div>
                            <div className="row px-4">
                                <div className="col">
                                    <div className="card h-100">
                                        <div className="card-header">
                                            Manage Users
                                        </div>
                                        <div className="card-body">
                                            <h5 className="card-title">User management panel</h5>
                                            <p className="card-text">The module enables the administration of system user accounts.</p>
                                            <Link to="/admin/users" className="btn btn-search-outline">Open panel</Link>
                                        </div>
                                    </div>
                                </div>
                                <div className="col">
                                    <div className="card h-100">
                                        <div className="card-header">
                                            Manage API requests
                                        </div>
                                        <div className="card-body">
                                            <h5 className="card-title">API request management panel</h5>
                                            <p className="card-text">The module manages the API interface and its requests.</p>
                                            <Link to="/admin/api" className="btn btn-search-outline">Open panel</Link>
                                        </div>
                                    </div>
                                </div>
                                <div className="col">
                                    <div className="card h-100">
                                        <div className="card-header">
                                            Manage Emulators
                                        </div>
                                        <div className="card-body">
                                            <h5 className="card-title">Server emulators management panel</h5>
                                            <p className="card-text">The module provides management of virtual devices installed on the server.</p>
                                            <Link to="/admin/emulators" className="btn btn-search-outline">Open panel</Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="row p-3"></div>
                       </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Dashboard;
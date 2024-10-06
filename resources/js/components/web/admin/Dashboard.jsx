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

import { Link } from "react-router-dom";


const Dashboard = () => {

    // Component body
    return (
        <div className="Dashboard container-fluid">
            <div className="row">
                <Sidebar sidebarType="admin" />
                <div className="col-md-10 px-0">
                    <Navbar />
                    <div className="page container-fluid pt-md-3 px-4">
                       <div className="container-fluid shadow bg-white text-dark p-3">
                            <div className="row p-3">
                                <div className="col">
                                    <h4 className="p-2">Dashboard</h4>
                                </div>
                                <div className="col"></div>
                                <div className="col"></div>
                            </div>
                            <div className="row px-4">
                                <p>
                                    Welcome to admin panel. There you can manage web application settings like app users,
                                    created hashes, server emulators or API interface.
                                </p>
                            </div>
                            <div className="row p-3"></div>
                            <div className="row px-4">
                                <div className="col">
                                    <div class="card">
                                        <div class="card-header">
                                            Manage Users
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title">User management panel</h5>
                                            <p class="card-text">The module enables the administration of system user accounts.</p>
                                            <Link to="/admin/users" class="btn btn-search-outline">Open panel</Link>
                                        </div>
                                    </div>
                                </div>
                                <div className="col">
                                    <div class="card">
                                        <div class="card-header">
                                            Manage API requests
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title">API request management panel</h5>
                                            <p class="card-text">The module manages the API interface and its requests.</p>
                                            <Link to="/admin/api" class="btn btn-search-outline">Open panel</Link>
                                        </div>
                                    </div>
                                </div>
                                <div className="col">
                                    <div class="card">
                                        <div class="card-header">
                                            Manage Emulators
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title">Server emulators management panel</h5>
                                            <p class="card-text">The module provides management of virtual devices installed on the server.</p>
                                            <Link to="/admin/emulators" class="btn btn-search-outline">Open panel</Link>
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
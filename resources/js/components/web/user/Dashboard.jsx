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
                <Sidebar sidebarType="basic_user" />
                <div className="col px-0" style={{flex: '1'}}>
                    <Navbar />
                    <div className="page container-fluid pt-md-3 px-4">
                        <div className="container-fluid shadow bg-white text-dark p-3">
                            <PageHeader 
                                title="Dashboard"
                                description="Welcome to user panel. There you can manage your profile info, like username, email. You can show information's about your API requests or show data about your liked apps."
                            />
                            <div className="row p-3"></div>
                            <div className="row px-4">
                                <div className="col">
                                    <div className="card h-100">
                                        <div className="card-header">
                                            Profile page
                                        </div>
                                        <div className="card-body">
                                            <h5 className="card-title">User Profile settings</h5>
                                            <p className="card-text">On this page you can manage your user credentials.</p>
                                            <Link to="/user/profile" className="btn btn-search-outline">Open page</Link>
                                        </div>
                                    </div>
                                </div>
                                <div className="col">
                                    <div className="card h-100">
                                        <div className="card-header">
                                            API requests page
                                        </div>
                                        <div className="card-body">
                                            <h5 className="card-title">API request settings</h5>
                                            <p className="card-text">On this page you can see infomrations about your API requests and your API keys.</p>
                                            <Link to="/user/api" className="btn btn-search-outline">Open panel</Link>
                                        </div>
                                    </div>
                                </div>
                                <div className="col">
                                    <div className="card h-100">
                                        <div className="card-header">
                                            Liked apps page
                                        </div>
                                        <div className="card-body">
                                            <h5 className="card-title">Liked apps settings</h5>
                                            <p className="card-text">On this page you can setup custom liked apps list and show info about this apps, like hashes, versions...</p>
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
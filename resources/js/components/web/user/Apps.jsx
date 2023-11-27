import React, { useState } from "react";
import ReactDOM from "react-dom";

import Navbar from "../partials/auth/Navbar";
import Sidebar from "../partials/auth/Sidebar";
import TableComponent from "../partials/TableComponent";


const Apps = () => {
    return (
        <div className="Dashboard container-fluid">
            <div className="row">
                <Sidebar sidebarType="basic_user" />
                <div className="col-md-10 px-0">
                    <Navbar />
                    <div className="page container pt-md-3">
                       <div className="container shadow bg-white text-dark p-3">
                            <div className="row p-3">
                                <div className="col">
                                    <h4 className="p-2">My Applications</h4>
                                </div>
                                <div className="col"></div>
                                <div className="col"></div>
                            </div>
                       </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Apps;
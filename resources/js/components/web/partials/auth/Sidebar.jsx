/**
 * @file Sidebar.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React from "react";
import ReactDOM from "react-dom";

import { Link } from "react-router-dom";


const Sidebar = ({sidebarType}) => {

    // Sidebar items for admin users
    const sidebar_items_admin = [
        {id:1, text: "Dashboard" , url: "/admin/dashboard", icon_class: "fa-solid fa-house pe-2"},
        {id:2, text: "API requests" , url: "/admin/api", icon_class: "fa-solid fa-code pe-2"},
        {id:3, text: "Hashes" , url: "/admin/hashes", icon_class: "fa-solid fa-hashtag pe-2"},
        {id:4, text: "Custom Hashes" , url: "/admin/custom-hash-types", icon_class: "fa-solid fa-puzzle-piece pe-2"},
        {id:5, text: "Settings" , url: "/admin/settings", icon_class: "fa-solid fa-gear pe-2"},
        {id:6, text: "Users" , url: "/admin/users", icon_class: "fa-solid fa-users pe-2"},
        {id:7, text: "Files" , url: "/admin/files", icon_class: "fa-solid fa-file pe-2"},
        {id:8, text: "Emulators" , url: "/admin/emulators", icon_class: "fa-solid fa-server pe-2"},
    ];

    // Sidebar items for basic users
    const sidebar_items_user = [
        {id:1, text: "Dashboard" , url: "/user/dashboard", icon_class: "fa-solid fa-house pe-2"},
        {id:2, text: "API requests" , url: "/user/api", icon_class: "fa-solid fa-code pe-2"},
        {id:3, text: "Custom Hashes" , url: "/user/custom-hash-types", icon_class: "fa-solid fa-puzzle-piece pe-2"},
        {id:4, text: "Profile" , url: "/user/profile", icon_class: "fa-solid fa-user pe-2"},
        {id:5, text: "My Apps" , url: "/user/applications", icon_class: "fa-solid fa-heart pe-2"},
    ];

    let sidebar_items = (sidebarType == "admin") ? sidebar_items_admin : sidebar_items_user;

    // Component body
    return (
        <div className="p-3 text-white bg-dark col vh-100" style={{minWidth: '200px', maxWidth: '250px'}}>
            <Link className="d-flex align-items-center px-3 mb-3 mb-md-0 me-md-auto text-white text-decoration-none" to="/">
                <div className="sidebar-brand-text mx-3 text-truncate" style={{maxWidth: '150px'}}>HashApp generator</div>
            </Link>
            <hr />
            <ul className="nav nav-pills flex-column mb-auto">
                {
                    sidebar_items?.map(sidebar_item => {
                        return (
                            <li className="nav-item" key={sidebar_item.id}>
                                <Link className="nav-link text-white d-flex align-items-center" to={sidebar_item.url} style={{whiteSpace: 'nowrap'}}>
                                    <i className={sidebar_item.icon_class} style={{minWidth: '20px'}} />
                                    <span className="text-truncate ms-2" style={{maxWidth: '120px'}}>{sidebar_item.text}</span>
                                </Link>
                            </li>
                        );
                    })
                }
            </ul>
        </div>
    );
};

export default Sidebar;
import React from "react";
import ReactDOM from "react-dom";

import { Link } from "react-router-dom";

const Sidebar = ({sidebarType}) => {

    const sidebar_items_admin = [
        {id:1, text: "Dashboard" , url: "/admin/dashboard", icon_class: "fa-solid fa-house pe-2"},
        {id:2, text: "API requests" , url: "/admin/api", icon_class: "fa-solid fa-code pe-2"},
        {id:3, text: "Hashes" , url: "/admin/hashes", icon_class: "fa-solid fa-hashtag pe-2"},
        {id:4, text: "Settings" , url: "/admin/settings", icon_class: "fa-solid fa-gear pe-2"},
        {id:5, text: "Users" , url: "/admin/users", icon_class: "fa-solid fa-users pe-2"},
    ];

    const sidebar_items_user = [
        {id:1, text: "Dashboard" , url: "/user/dashboard", icon_class: "fa-solid fa-house pe-2"},
        {id:2, text: "API requests" , url: "/user/api", icon_class: "fa-solid fa-code pe-2"},
        {id:3, text: "Hashes" , url: "/user/hashes", icon_class: "fa-solid fa-hashtag  pe-2"},
        {id:4, text: "Profile" , url: "/user/settings", icon_class: "fa-solid fa-user pe-2"},
        {id:5, text: "My Apps" , url: "/user/applications", icon_class: "fa-solid fa-heart pe-2"},
    ];

    let sidebar_items = (sidebarType == "admin") ? sidebar_items_admin : sidebar_items_user;

    return (
        <div className="p-3 text-white bg-dark col-md-2 vh-100">
            <Link className="d-flex align-items-center px-3 mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <div className="sidebar-brand-icon">
                    <i className="fa-brands fa-android"></i>
                </div>
                <div className="sidebar-brand-text mx-3">HashApp generator</div>
            </Link>
            <hr />
            <ul className="nav nav-pills flex-column mb-auto">
                {
                    sidebar_items?.map(sidebar_item => {
                        return (
                            <li className="nav-item" key={sidebar_item.id}>
                                <Link className="nav-link text-white" to={sidebar_item.url}>
                                    <i className={sidebar_item.icon_class} />{sidebar_item.text}
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
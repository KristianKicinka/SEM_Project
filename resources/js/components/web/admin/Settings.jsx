import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";

import Navbar from "../partials/auth/Navbar";
import Sidebar from "../partials/auth/Sidebar";
import TableComponent from "../partials/TableComponent";

import AuthUser from "../../../AuthUser";


const Settings = () => {

    const [apiKey, setApiKey] = useState("");
    const {http, token, user} = AuthUser();

    const [fetchDataState, setFetchDataState] = useState(false);

    const generateApiKey = async (e) => {
        e.preventDefault();
        try {
            let resp = await http.post('/admin/api-key-generate', {user_id:user.id});
            setFetchDataState(prevState => !prevState);
            setApiKey(resp.data.api_auth_key ? resp.data.api_auth_key : "" );
        } catch (error) {
            console.log(error);
        }
    }

    const fetchData = async () => {
        try {
            let resp = await http.post('/admin/get-api-key', {user_id:user.id});
            setApiKey(resp.data.api_auth_key ? resp.data.api_auth_key : "" );
        } catch (error) {
            console.log(error);
        }
    }
    
    useEffect(() => {
        fetchData();
        /*const interval = setInterval(() => {fetchData()}, 3000);
        return () => clearInterval(interval);*/
    }, [fetchDataState]);

    return (
        <div className="Settings container-fluid">
            <div className="row">
                <Sidebar sidebarType="admin" />
                <div className="col-md-10 px-0">
                    <Navbar />
                    <div className="page container-fluid pt-md-3 px-4">
                       <div className="container-fluid shadow bg-white text-dark p-3">
                            <div className="row p-3">
                                <div className="col">
                                    <h4 className="p-2">Settings</h4>
                                </div>
                                <div className="col"></div>
                                <div className="col"></div>
                            </div>
                            <div className="row p-3">
                                <div className="col-12">
                                    <form className="form row g-3" method="post" noValidate onSubmit={generateApiKey}>
                                        <div className="col-auto">
                                            <label 
                                                className="pt-2 text text-lg" 
                                                htmlFor="api_auth_key" 
                                                >API auth key generator</label>
                                        </div>
                                        <div className="col-md-4">
                                            <input className="form-control" 
                                                   type="text" 
                                                   value={apiKey} 
                                                   aria-label="api key input"
                                                   placeholder="Auth API key" 
                                                   readOnly />
                                        </div>
                                        <div className="col-auto">
                                            <input
                                                type="submit" 
                                                name="submit" 
                                                className="btn btn-search text-light btn-md" 
                                                value="Generate API key" 
                                                />
                                        </div>
                                    </form>
                                </div>
                                <div className="col"></div>
                            </div>
                       </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Settings;
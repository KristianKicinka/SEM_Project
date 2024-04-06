import React, { useState } from "react";
import ReactDOM from "react-dom";

import Navbar from "./partials/Navbar";
import MainPageImage from "../../../../public/img/main_page.png";

const AboutPage = () => {
    return (
        <div className="AboutPage bg-primary bg-gradient pt-5 vh-100">
            <Navbar />
            <div className="container pt-5">
                <div className="row">
                    <div className="card bg-white text-dark p-3">
                        <div className="card-body">
                            <h3 className="card-title">About project</h3>
                            <div className="row">
                                <div className="col">
                                    <p className="card-text">
                                    Nowadays, mobile applications represent a key element for the daily fulfillment of users' needs. 
                                    Applications represent an irreplaceable place in people's lives. The security aspects
                                    of the communication of these applications are critical in some cases, especially when 
                                    maintaining the confidentiality and integrity of information when performing banking transactions
                                    or interacting on social networks.
                                    <br/>
                                    Securing these aspects is done through TLS encryption and integrity checks. This protocol poses
                                    a challenge to network administrators of local and corporate networks in the current need to
                                    guarantee private communications and network security, because it is not possible to easily
                                    identify potentially dangerous as well as common applications on the network. 
                                    By using TLS fingerprints of mobile applications, this problem can be solved to some extent.
                                    <br/>
                                    This work is focused on the description of the development of a new tool for the automated 
                                    creation of TLS fingerprints of mobile applications. The tool represents an innovative approach
                                    to support network administrators in analyzing and monitoring potentially dangerous 
                                    mobile applications in their managed networks. Its goal is to provide an effective means of 
                                    maintaining security while enabling a thorough analysis of network communications to reliably
                                    respond to potential threats.
                                    <br/>
                                    The benefit of this platform should be the streamlining and automation of a certain part of
                                    the work of network administrators. This should minimize the need to manually go through and 
                                    evaluate large volumes of data containing the communication of mobile applications.
                                    </p>
                                </div>
                                <div className="col">
                                    <img src={MainPageImage} className="w-100 shadow" alt="" />
                                </div>
                            </div>
                            <a href="/" className="btn btn-search text-light mt-4">
                                Get started
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default AboutPage;

/**
 * @file AppItem.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { useState, useEffect } from 'react';
import ReactDOM from "react-dom";
import axios from "axios";

import { setNewActiveChannel, setNewActiveProcess } from '../../../processManagement';
import { toast } from 'react-toastify';
import LoadingModal from './LoadingModal';


const AppItem = ({ item, hashTypes }) => {

    const [showLoading, setShowLoading] = useState(false);
    const [channelID, setChannelID] = useState(false);
    const [processes, setProcesses] = useState([]);

    /**
     * @brief The function ensures calling API request for creation hashes from application name
     * @param {*} event OnClick event
     */
    const createHash = async (event) => {
        event.preventDefault();

        if(hashTypes.length === 0){
            toast.error('Hash type must be selected!');
            return;
        }

        let channel_id = setNewActiveChannel();
        setChannelID(channel_id);

        let data = {
            'package_name': item.package_name,
            'hash_types': hashTypes,
            'channel_id': channel_id,
        }

        try {
            let results = await axios.post('/api/create-hash-appname', data);

            setProcesses(results.data.processes);
            setShowLoading(true);
        } catch (error) {
            setShowLoading(false);
            toast.error('Hash generation error!');
            console.log(`ERROR: ${error}`);
        }
    }

    /**
     * @brief The function ensures close loading modal box
     */
    const closeLoading = () => {
        setShowLoading(false);
    }

    useEffect(() => {
       
    }, []);

    // Component body
    return (
        <div>
            <a href={item.package_name} onClick={createHash} className="text-decoration-none" >
                <div className="card">
                    <div className="card-body text-dark">
                        <div className="container">
                            <div className="row gx-2">
                                <div className="col-sm-4">
                                    <img className="w-100" src={item.thumbnail} alt="AppIcon" />
                                </div>
                                <div className="col-sm-8">
                                    <h6 className="card-title">{item.app_name}</h6>
                                    <span className="card-text">
                                        {item.package_name}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
            {showLoading && (
                <LoadingModal processes={processes} channel_id={channelID} onClose={closeLoading} hashTypes={hashTypes} />
            )}
        </div>
    );
};

export default AppItem;

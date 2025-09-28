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
    const [imageError, setImageError] = useState(false);
    const [imageLoading, setImageLoading] = useState(true);

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
                <div className="card h-100 shadow-sm">
                    <div className="card-body text-dark d-flex flex-column">
                        <div className="d-flex align-items-center mb-3">
                            <div className="me-3 flex-shrink-0 position-relative">
                                {imageLoading && !imageError && (
                                    <div 
                                        className="rounded-circle d-flex align-items-center justify-content-center"
                                        style={{ 
                                            width: '60px', 
                                            height: '60px', 
                                            backgroundColor: '#f8f9fa',
                                            border: '2px solid #f8f9fa'
                                        }}
                                    >
                                        <div className="spinner-border spinner-border-sm text-muted" role="status">
                                            <span className="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                )}
                                <img 
                                    className={`rounded-circle ${imageLoading ? 'd-none' : ''}`}
                                    src={imageError ? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiByeD0iMzAiIGZpbGw9IiNmOGY5ZmEiLz4KPHN2ZyB4PSIxNSIgeT0iMTUiIHdpZHRoPSIzMCIgaGVpZ2h0PSIzMCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSIjNmM3NTdkIj4KPHBhdGggZD0iTTEyIDJMMTMuMDkgOC4yNkwyMCA5TDEzLjA5IDE1Ljc0TDEyIDIyTDEwLjkxIDE1Ljc0TDQgOUwxMC45MSA4LjI2TDEyIDJaIi8+Cjwvc3ZnPgo8L3N2Zz4K' : item.thumbnail}
                                    alt="AppIcon" 
                                    style={{ 
                                        width: '60px', 
                                        height: '60px', 
                                        objectFit: 'cover',
                                        border: '2px solid #f8f9fa'
                                    }}
                                    onLoad={() => setImageLoading(false)}
                                    onError={(e) => {
                                        setImageError(true);
                                        setImageLoading(false);
                                        console.warn(`Failed to load image for ${item.app_name}:`, item.thumbnail);
                                    }}
                                />
                            </div>
                            <div className="flex-grow-1 min-width-0">
                                <h6 className="card-title mb-1 text-truncate app-card-text" title={item.app_name}>
                                    {item.app_name}
                                </h6>
                                <small className="text-muted text-truncate d-block app-card-text" title={item.package_name}>
                                    {item.package_name}
                                </small>
                            </div>
                        </div>
                        <div className="mt-auto">
                            <div className="d-flex justify-content-between align-items-center">
                                <small className="text-muted">
                                    <i className="fa-solid fa-mobile-screen me-1"></i>
                                    Mobile App
                                </small>
                                <button 
                                    className="btn btn-sm bg-orange text-white rounded-pill px-3"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        createHash(e);
                                    }}
                                >
                                    <i className="fa-solid fa-play me-1"></i>
                                    Generate
                                </button>
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

/**
 * @file CreateEmulator.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { useState } from "react";
import ReactDOM from "react-dom";

import AuthUser from "../../../../../AuthUser";
import { Modal, Button } from 'react-bootstrap';


const CreateEmulator = ({show, handleClose, setFetchDataState}) => {

    const [containerName, setContainerName] = useState("");
    const [networkName, setNetworkName] = useState("");
    const [mountSource, setMountSource] = useState("");
    const [mountTarget, setMountTarget] = useState("");
    const [image, setImage] = useState("")
    const [memory, setMemory] = useState(128);
    const [cpuCount, setCpuCount] = useState(1);

    const [errors, setErrors] = useState({});
    const {http} = AuthUser();

    /**
     * @brief The function ensures creating new users
     * @param {*} e OnClick event
     */
    const createNewEmulator = async (e) => {
        e.preventDefault();

        const emulatorData = {
            container_name:containerName, network_name:networkName, mount_source:mountSource,
            mount_target:mountTarget, image:image, memory:memory, cpu_count:cpuCount
        };

        try {
            let resp = await http.post('/admin/emulator/create', emulatorData);

            setFetchDataState(prevState => !prevState);
            clearInputs();
            handleClose();
        } catch (error) {
            if (error.response.status === 400) {
                setErrors(error.response.data.errors);
            }
            console.log(error);
        }
    }

    /**
     * @brief The function ensures clear inputs
     */
    const clearInputs = () => {
        setContainerName('');
        setNetworkName('');
        setMountSource('');
        setMountTarget('');
        setImage('');
        setMemory(128);
        setCpuCount(1);
    }

    // Component body
    return (
        <Modal show={show} onHide={handleClose}>
            <Modal.Header closeButton>
                <Modal.Title>Create new user</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="container-fluid">
                    <div className="row">
                        <div className="col">
                            <form className="form" method="post" noValidate onSubmit={createNewEmulator} >
                                <div className="form-group py-2">
                                    <label htmlFor="containerName" className="text-dark">Container name:</label><br/>
                                    <input 
                                        type="text" 
                                        name="containerName" 
                                        id="containerName"
                                        placeholder="container name" 
                                        value={containerName}
                                        onChange={(e) => setContainerName(e.target.value)}
                                        className="form-control"/>
                                    {errors.container_name && <span className="error text-danger">{errors.container_name[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="networkName" className="text-dark">Network interface name:</label><br/>
                                    <input 
                                        type="text" 
                                        name="networkName" 
                                        id="networkName"
                                        placeholder="network interface name"
                                        value={networkName}
                                        onChange={(e) => setNetworkName(e.target.value)} 
                                        className="form-control"/>
                                    {errors.network_name && <span className="error text-danger">{errors.network_name[0]}</span>}
                                </div>           
                                <div className="form-group py-2">
                                    <label htmlFor="mountSource" className="text-dark">Mount source:</label><br/>
                                    <input 
                                        type="text" 
                                        name="mountSource" 
                                        id="mountSource"
                                        placeholder="mount source" 
                                        value={mountSource}
                                        onChange={(e) => setMountSource(e.target.value)}
                                        className="form-control"/>
                                    {errors.mount_source && <span className="error text-danger">{errors.mount_source[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="mountTarget" className="text-dark">Mount target:</label><br/>
                                    <input 
                                        type="text" 
                                        name="mountTarget" 
                                        id="mountTarget"
                                        placeholder="mount target" 
                                        value={mountTarget}
                                        onChange={(e) => setMountTarget(e.target.value)}
                                        className="form-control"/>
                                    {errors.mount_target && <span className="error text-danger">{errors.mount_target[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="image" className="text-dark">Image:</label><br/>
                                    <input 
                                        type="text"
                                        name="image" 
                                        id="image"
                                        placeholder="image"
                                        value={image}
                                        onChange={(e) => setImage(e.target.value)} 
                                        className="form-control" />
                                    {errors.image && <span className="error text-danger">{errors.image[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="memory" className="text-dark">Memory:</label><br/>
                                    <input 
                                        type="number"
                                        name="memory" 
                                        id="memory"
                                        placeholder="memory"
                                        value={memory}
                                        onChange={(e) => setMemory(e.target.value)} 
                                        className="form-control" />
                                    {errors.memory && <span className="error text-danger">{errors.memory[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="cpuCount" className="text-dark">CPU count:</label><br/>
                                    <input 
                                        type="number"
                                        name="cpuCount" 
                                        id="cpuCount"
                                        placeholder="cpu count"
                                        value={cpuCount}
                                        onChange={(e) => setCpuCount(e.target.value)} 
                                        className="form-control" />
                                    {errors.cpu_count && <span className="error text-danger">{errors.cpu_count[0]}</span>}
                                </div>
                                
                                <div className="form-group pt-3 text-center">
                                    <input 
                                        type="submit" 
                                        name="submit" 
                                        className="btn btn-search text-light btn-md col-md-10" 
                                        value="Create new emulator"/>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Modal.Body>
        </Modal>
    );
};

export default CreateEmulator;
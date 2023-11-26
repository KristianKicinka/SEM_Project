import React, { useState } from "react";
import ReactDOM from "react-dom";

import AuthUser from "../../../../../AuthUser";
import { Modal, Button, Form } from 'react-bootstrap';

const CreateUser = ({show, handleClose, setFetchDataState}) => {

    const [appNameText, setAppNameText] = useState("");
    const [appNamePcap, setAppNamePcap] = useState("");
    const [packageNameText, setPackageNameText] = useState("");
    const [packageNamePcap, setPackageNamePcap] = useState("");
    const [appVersionText, setAppVersionText] = useState("");
    const [appVersionPcap, setAppVersionPcap] = useState("");
    const [hashTypesText, setHashTypesText] = useState([]);
    const [hashTypesPcap, setHashTypesPcap] = useState([]);
    const [pcapFile, setPcapFile] = useState("");
    const [isMalwareText, setIsMalwareText] = useState(false);
    const [isMalwarePcap, setIsMalwarePcap] = useState(false);
    const [hash, setHash] = useState("");

    const [errors, setErrors] = useState({});
    const {http} = AuthUser();

    const checkboxChangePcap = (e) => {

        let newHashesTypes = [...hashTypesPcap, e.target.id];

        if (hashTypesPcap.includes(e.target.id))
            newHashesTypes = newHashesTypes.filter(hashType => hashType !== e.target.id);
   
        setHashTypesPcap(newHashesTypes);
    }

    const checkboxChangeText = (e) => {

        let newHashesTypes = [...hashTypesText, e.target.id];

        if (hashTypesText.includes(e.target.id))
            newHashesTypes = newHashesTypes.filter(hashType => hashType !== e.target.id);
   
        setHashTypesText(newHashesTypes);
    }

    const createHashFromTextInput = async (e) => {
        e.preventDefault();

        const data = {
            app_name_text:appNameText, 
            package_name_text:packageNameText, 
            app_version_text:appVersionText,
            hash_types_text:hashTypesText,
            is_malware_text:isMalwareText,
            hash:hash
        };

        console.log(data);

        try {
            let resp = await http.post('/admin/hash/create/text-input', data);
            console.log(resp);
            setFetchDataState(prevState => !prevState);
            clearInputs();
            handleClose();
        } catch (error) {
            console.log(error);
            if (error.response.status === 400) {
                setErrors(error.response.data.errors);
            }
            console.log(error);
        }
    }

    const createHashFromPcapFile = async (e) => {
        e.preventDefault();

        const data = new FormData();
        data.append("app_name_pcap", appNamePcap);
        data.append("package_name_pcap", packageNamePcap);
        data.append("app_version_pcap", appVersionPcap);
        data.append("hash_types_pcap", JSON.stringify(hashTypesPcap));
        data.append("is_malware_pcap", isMalwarePcap);
        data.append("pcap_file", pcapFile);

        try {
            let resp = await http.post('/admin/hash/create/pcap-file', data);
            console.log(resp);
            setFetchDataState(prevState => !prevState);
            clearInputs();
            handleClose();
        } catch (error) {
            console.log(error);
            if (error.response.status === 400) {
                setErrors(error.response.data.errors);
            }
            console.log(error);
        }
    }

    const clearInputs = () => {
       
    }

    

    return (
        <Modal show={show} onHide={handleClose} size="lg" >
            <Modal.Header closeButton>
                <Modal.Title>Create new hash</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="container-fluid">
                    <div className="row gap-3">
                        <div className="col border rounded p-3">
                            <span className="text text-bold">Create hash from text input:</span>
                            <form className="form" method="post" noValidate onSubmit={createHashFromTextInput} >
                                <div className="form-group py-2">
                                    <label htmlFor="app_name_text" className="text-dark">Application name:</label><br/>
                                    <input 
                                        type="text" 
                                        name="app_name_text" 
                                        id="app_name_text"
                                        placeholder="Application name" 
                                        value={appNameText}
                                        onChange={(e) => setAppNameText(e.target.value)}
                                        className="form-control"/>
                                    {errors.app_name_text && <span className="error text-danger">{errors.app_name_text[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="package_name_text" className="text-dark">Package name:</label><br/>
                                    <input 
                                        type="text" 
                                        name="package_name_text" 
                                        id="package_name_text"
                                        placeholder="Package name"
                                        value={packageNameText}
                                        onChange={(e) => setPackageNameText(e.target.value)} 
                                        className="form-control"/>
                                    {errors.package_name_text && <span className="error text-danger">{errors.package_name_text[0]}</span>}
                                </div>           
                                <div className="form-group py-2">
                                    <label htmlFor="app_version_text" className="text-dark">App version:</label><br/>
                                    <input 
                                        type="text" 
                                        name="app_version_text" 
                                        id="app_version_text"
                                        placeholder="App version" 
                                        value={appVersionText}
                                        onChange={(e) => setAppVersionText(e.target.value)}
                                        className="form-control"/>
                                    {errors.app_version_text && <span className="error text-danger">{errors.app_version_text[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="is_malware_text" className="text-dark">Is malware:</label><br/>
                                    <select 
                                        className="form-select" 
                                        aria-label="Select if is a malware" 
                                        onChange={(e) => setIsMalwareText(e.target.value)}
                                    >
                                        <option value={false}>is not malware</option>
                                        <option value={true}>malware</option>
                                    </select>
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="hash" className="text-dark">Hash:</label><br/>
                                    <input 
                                        type="text"
                                        name="hash" 
                                        id="hash"
                                        placeholder="Hash"
                                        value={hash}
                                        onChange={(e) => setHash(e.target.value)} 
                                        className="form-control" />
                                    {errors.hash && <span className="error text-danger">{errors.hash[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="hash_type_text" className="text-dark">Select hash types:</label><br/>
                                    <Form.Check onChange={checkboxChangeText} inline label="JA3" name="JA3_checkbox" type='checkbox' id='JA3' />
                                    <Form.Check onChange={checkboxChangeText} inline label="JA3S" name="JA3S_checkbox" type='checkbox' id='JA3S' />
                                    <Form.Check disabled onChange={checkboxChangeText} inline label="Flowmon" name="NetFlow_checkbox" type='checkbox' id='NetFlow' />
                
                                </div>
                                <div className="form-group pt-3 text-center">
                                    <input 
                                        type="submit" 
                                        name="submit" 
                                        className="btn btn-search text-light btn-md col-md-10" 
                                        value="Create new hash"/>
                                </div>
                            </form>
                        </div>
                        <div className="col border rounded p-3">
                            <span className="text text-bold">Create hash from pcap file:</span>
                            <form className="form" method="post" noValidate onSubmit={createHashFromPcapFile} encType="multipart/form-data" >
                                <div className="form-group py-2">
                                    <label htmlFor="app_name_pcap" className="text-dark">Application name:</label><br/>
                                    <input 
                                        type="text" 
                                        name="app_name_pcap" 
                                        id="app_name_pcap"
                                        placeholder="Application name" 
                                        value={appNamePcap}
                                        onChange={(e) => setAppNamePcap(e.target.value)}
                                        className="form-control"/>
                                    {errors.appNamePcap && <span className="error text-danger">{errors.app_name_pcap[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="package_name_pcap" className="text-dark">Package name:</label><br/>
                                    <input 
                                        type="text" 
                                        name="package_name_pcap" 
                                        id="package_name_pcap"
                                        placeholder="Package name"
                                        value={packageNamePcap}
                                        onChange={(e) => setPackageNamePcap(e.target.value)} 
                                        className="form-control"/>
                                    {errors.package_name_pcap && <span className="error text-danger">{errors.package_name_pcap[0]}</span>}
                                </div>           
                                <div className="form-group py-2">
                                    <label htmlFor="app_version_pcap" className="text-dark">App version:</label><br/>
                                    <input 
                                        type="text" 
                                        name="app_version_pcap" 
                                        id="app_version_pcap"
                                        placeholder="App version" 
                                        value={appVersionPcap}
                                        onChange={(e) => setAppVersionPcap(e.target.value)}
                                        className="form-control"/>
                                    {errors.app_version_pcap && <span className="error text-danger">{errors.app_version_pcap[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="is_malware" className="text-dark">Is malware:</label><br/>
                                    <select 
                                        className="form-select" 
                                        aria-label="Select if is a malware" 
                                        onChange={(e) => setIsMalwarePcap(e.target.value)}
                                    >
                                        <option value={false}>is not malware</option>
                                        <option value={true}>malware</option>
                                    </select>
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="hash" className="text-dark">Pcap file:</label><br/>
                                    <Form.Control type="file" className='col'
                                        onChange={e=>{setPcapFile(e.target.files[0])}} accept='.pcap' required />
                                    {errors.pcap_file && <span className="error text-danger">{errors.pcap_file[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="hash_type_pcap" className="text-dark">Select hash types:</label><br/>
                                    <Form.Check onChange={checkboxChangePcap} inline label="JA3" name="JA3_checkbox" type='checkbox' id='JA3' />
                                    <Form.Check onChange={checkboxChangePcap} inline label="JA3S" name="JA3S_checkbox" type='checkbox' id='JA3S' />
                                    <Form.Check disabled onChange={checkboxChangePcap} inline label="Flowmon" name="NetFlow_checkbox" type='checkbox' id='NetFlow' />
                                </div>
                                <div className="form-group pt-3 text-center">
                                    <input 
                                        type="submit" 
                                        name="submit" 
                                        className="btn btn-search text-light btn-md col-md-10" 
                                        value="Create new hash"/>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Modal.Body>
        </Modal>
    );
};

export default CreateUser;
import React, { useState } from "react";
import ReactDOM from "react-dom";

import AuthUser from "../../../../../AuthUser";
import { Modal, Button, Form } from 'react-bootstrap';

const CreateUser = ({show, handleClose, setFetchDataState}) => {

    const [appNameText, setAppNameText] = useState("");
    const [packageNameText, setPackageNameText] = useState("");
    const [appVersionText, setAppVersionText] = useState("");
    const [sni, setSni] = useState("");
    const [ja3Hash, setJa3Hash] = useState("");
    const [ja3sHash, setJa3sHash] = useState("");
    const [ja4Hash, setJa4Hash] = useState("");
    const [ja4sHash, setJa4sHash] = useState("");
    const [isMalwareText, setIsMalwareText] = useState(false);
    const [isDangerousText, setIsDangerousText] = useState(false);


    const [appNamePcap, setAppNamePcap] = useState("");
    const [packageNamePcap, setPackageNamePcap] = useState("");
    const [appVersionPcap, setAppVersionPcap] = useState("");
    const [pcapFile, setPcapFile] = useState(null);
    const [isMalwarePcap, setIsMalwarePcap] = useState(false);
    const [isDangerousPcap, setIsDangerousPcap] = useState(false);

    const [errors, setErrors] = useState({});
    const {http, http_file} = AuthUser();

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
        data.append("is_malware_pcap", isMalwarePcap);
        data.append("is_dangerous_pcap", isDangerousPcap);
        data.append("pcap_file", pcapFile);

        try {
            let resp = await http_file.post('/admin/hash/create/pcap-file', data);
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
                                    <label htmlFor="app_name" className="text-dark">App name:</label><br/>
                                    <input 
                                        type="text" 
                                        name="app_name" 
                                        id="app_name"
                                        placeholder="app name"
                                        value={appNameText}
                                        onChange={(e) => setAppNameText(e.target.value)}
                                        className="form-control"/>
                                    {errors.app_name && <span className="error text-danger">{errors.app_name[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="package_name" className="text-dark">Package name:</label><br/>
                                    <input 
                                        type="text" 
                                        name="package_name" 
                                        id="package_name"
                                        placeholder="package_name"
                                        value={packageNameText}
                                        onChange={(e) => setPackageNameText(e.target.value)} 
                                        className="form-control"/>
                                    {errors.package_name && <span className="error text-danger">{errors.package_name[0]}</span>}
                                </div>           
                                <div className="form-group py-2">
                                    <label htmlFor="version" className="text-dark">Version:</label><br/>
                                    <input 
                                        type="text" 
                                        name="version" 
                                        id="version"
                                        placeholder="version" 
                                        value={appVersionText}
                                        onChange={(e) => setAppVersionText(e.target.value)}
                                        className="form-control"/>
                                    {errors.version && <span className="error text-danger">{errors.version[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="sni" className="text-dark">SNI:</label><br/>
                                    <input 
                                        type="text" 
                                        name="sni" 
                                        id="sni"
                                        placeholder="sni" 
                                        value={sni}
                                        onChange={(e) => setSni(e.target.value)}
                                        className="form-control"/>
                                    {errors.sni && <span className="error text-danger">{errors.sni[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="ja3_hash" className="text-dark">JA3 hash:</label><br/>
                                    <input 
                                        type="text" 
                                        name="ja3_hash" 
                                        id="ja3_hash"
                                        placeholder="ja3_hash" 
                                        value={ja3Hash}
                                        onChange={(e) => setJa3Hash(e.target.value)}
                                        className="form-control"/>
                                    {errors.ja3_hash && <span className="error text-danger">{errors.ja3_hash[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="ja3s_hash" className="text-dark">JA3S hash:</label><br/>
                                    <input 
                                        type="text" 
                                        name="ja3s_hash" 
                                        id="ja3s_hash"
                                        placeholder="ja3s_hash" 
                                        value={ja3sHash}
                                        onChange={(e) => setJa3sHash(e.target.value)}
                                        className="form-control"/>
                                    {errors.ja3s_hash && <span className="error text-danger">{errors.ja3s_hash[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="ja4_hash" className="text-dark">JA4 hash:</label><br/>
                                    <input 
                                        type="text" 
                                        name="ja4_hash" 
                                        id="ja4_hash"
                                        placeholder="ja4_hash" 
                                        value={ja4Hash}
                                        onChange={(e) => setJa4Hash(e.target.value)}
                                        className="form-control"/>
                                    {errors.ja4_hash && <span className="error text-danger">{errors.ja4_hash[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="ja4s_hash" className="text-dark">JA4S hash:</label><br/>
                                    <input 
                                        type="text" 
                                        name="ja4s_hash" 
                                        id="ja4s_hash"
                                        placeholder="ja4s_hash" 
                                        value={ja4sHash}
                                        onChange={(e) => setJa4sHash(e.target.value)}
                                        className="form-control"/>
                                    {errors.ja4s_hash && <span className="error text-danger">{errors.ja4s_hash[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="is_malware" className="text-dark">Is malware:</label><br/>
                                    <select 
                                        className="form-select" 
                                        aria-label="Select if is a malware"
                                        value={isMalwareText}
                                        onChange={(e) => setIsMalwareText(e.target.value)}
                                    >
                                        <option value={false}>is not malware</option>
                                        <option value={true}>malware</option>
                                    </select>
                                    {errors.is_malware && <span className="error text-danger">{errors.is_malware[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="is_dangerous" className="text-dark">Is dangerous:</label><br/>
                                    <select 
                                        className="form-select" 
                                        aria-label="Select if is a malware"
                                        value={isDangerousText}
                                        onChange={(e) => setIsDangerousText(e.target.value)}
                                    >
                                        <option value={false}>is not dangerous</option>
                                        <option value={true}>dangerous</option>
                                    </select>
                                    {errors.is_dangerous && <span className="error text-danger">{errors.is_dangerous[0]}</span>}
                                </div>
                                <div className="form-group pt-3 text-center">
                                    <input 
                                        type="submit" 
                                        name="submit" 
                                        className="btn btn-search text-light btn-md col-md-10" 
                                        value="Save data"/>
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
                                    <label htmlFor="is_dangerous" className="text-dark">Is dangerous:</label><br/>
                                    <select 
                                        className="form-select" 
                                        aria-label="Select if is a malware" 
                                        onChange={(e) => setIsDangerousPcap(e.target.value)}
                                    >
                                        <option value={false}>is not dangerous</option>
                                        <option value={true}>dangerous</option>
                                    </select>
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="hash" className="text-dark">Pcap file:</label><br/>
                                    <Form.Control type="file" className='col'
                                        onChange={e=>{setPcapFile(e.target.files[0])}} accept='.pcap' required />
                                    {errors.pcap_file && <span className="error text-danger">{errors.pcap_file[0]}</span>}
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
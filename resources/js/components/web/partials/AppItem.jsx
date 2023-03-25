import React from "react";
import ReactDOM from "react-dom";
import axios from "axios";

const AppItem = ({ item, handleShowLoading, handleCloseLoading, handleShowResults, setResults}) => {


    const createHash = (fileName) =>{
        console.log(fileName);

        let data = {
            'file_name': fileName,
            'apk_type': 'downloaded',
            'hash_type': 'ja3'
        }

        axios.post('/createHashFromApkFile', data).then( res => {
            console.log(res.data);
            setResults(res.data);
            handleCloseLoading();
            handleShowResults();
        });
    } 

    const getApkFile = (e) => {
        e.preventDefault();
        console.log(item.product_id);

        handleShowLoading();

        axios.post('/downloadApkFile', {'package_name': item.product_id}).then( res => {
            console.log(res.data);
            if(res.data != 'APK download failed!')
                createHash(res.data);
        });
    };

    return (
        <a href={item.title} onClick={getApkFile} className="text-decoration-none" >
            <div className="card">
                <div className="card-body text-dark">
                    <div className="container">
                        <div className="row gx-2">
                            <div className="col-sm-4">
                                <img src={item.thumbnail} alt="AppIcon" />
                            </div>
                            <div className="col-sm-8">
                                <h6 className="card-title">{item.title}</h6>
                                <span className="card-text">
                                    {item.product_id}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    );
};

export default AppItem;

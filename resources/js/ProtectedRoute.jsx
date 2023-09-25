import { Navigate} from "react-router-dom";

import AuthUser from "./AuthUser";

const ProtectedRoute = ({children, userType}) =>{
    const {user} = AuthUser();

    if(!user)
        return <Navigate to="/login" state={{error: 'user_not_found'}} />;

    if(user.role != userType)
        return <Navigate to="/login" state={{error: 'access_denied'}} />;

    return children;

}

export default ProtectedRoute;